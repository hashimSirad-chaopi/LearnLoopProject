<?php

use App\Models\User;
use App\Support\AdminDevices;
use App\Support\EmailOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

function makeOtpTestAdmin(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'admin',
        'status' => 'active',
    ], $attributes));
}

function seedAdminLoginOtp(User $admin, string $code = '123456'): void
{
    $email = strtolower(trim($admin->email));

    DB::table('email_otps')->insert([
        'email' => $email,
        'purpose' => EmailOtp::ADMIN_LOGIN,
        'code_hash' => hash_hmac(
            'sha256',
            $email.'|'.EmailOtp::ADMIN_LOGIN.'|'.$code,
            (string) config('app.key')
        ),
        'attempts' => 0,
        'expires_at' => now()->addMinutes(10),
        'last_sent_at' => now()->subSeconds(61),
        'token_hash' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function seedTrustedTestDevice(
    User $admin,
    string $token,
    $expiresAt = null
): int {
    return (int) DB::table('admin_trusted_devices')->insertGetId([
        'user_id' => $admin->id,
        'token_hash' => hash('sha256', $token),
        'label' => 'Chrome on Windows',
        'ip' => '127.0.0.1',
        'last_used_at' => now()->subDay(),
        'expires_at' => $expiresAt ?? now()->addDays(30),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('unverified admin is redirected away from protected admin pages', function () {
    $admin = makeOtpTestAdmin();

    $this->actingAs($admin)
        ->get(route('admin.home'))
        ->assertRedirect(route('admin.verify'));

    $this->assertNull(session('admin_verified_for'));
    $this->assertNotEmpty(session('admin_verify_intended'));
});

test('OTP input containing non-digit characters is rejected without using an attempt', function () {
    $admin = makeOtpTestAdmin();
    seedAdminLoginOtp($admin);

    $this->actingAs($admin)
        ->from(route('admin.verify'))
        ->post(route('admin.verify.check'), [
            'code' => '123456abc',
        ])
        ->assertRedirect(route('admin.verify'))
        ->assertSessionHasErrors('code');

    $this->assertNull(session('admin_verified_for'));

    $this->assertDatabaseHas('email_otps', [
        'email' => strtolower($admin->email),
        'purpose' => EmailOtp::ADMIN_LOGIN,
        'attempts' => 0,
    ]);
});

test('an incorrect OTP is rejected and increments the attempt count', function () {
    $admin = makeOtpTestAdmin();
    seedAdminLoginOtp($admin);

    $this->actingAs($admin)
        ->from(route('admin.verify'))
        ->post(route('admin.verify.check'), [
            'code' => '999999',
        ])
        ->assertRedirect(route('admin.verify'))
        ->assertSessionHasErrors('code');

    $this->assertNull(session('admin_verified_for'));

    $this->assertDatabaseHas('email_otps', [
        'email' => strtolower($admin->email),
        'purpose' => EmailOtp::ADMIN_LOGIN,
        'attempts' => 1,
    ]);
});

test('an expired OTP cannot verify an admin', function () {
    $admin = makeOtpTestAdmin();
    seedAdminLoginOtp($admin);

    DB::table('email_otps')
        ->where('email', strtolower($admin->email))
        ->where('purpose', EmailOtp::ADMIN_LOGIN)
        ->update(['expires_at' => now()->subMinute()]);

    $this->actingAs($admin)
        ->from(route('admin.verify'))
        ->post(route('admin.verify.check'), [
            'code' => '123456',
        ])
        ->assertRedirect(route('admin.verify'))
        ->assertSessionHasErrors('code');

    $this->assertNull(session('admin_verified_for'));

    $this->assertDatabaseMissing('email_otps', [
        'email' => strtolower($admin->email),
        'purpose' => EmailOtp::ADMIN_LOGIN,
    ]);
});

test('a valid OTP verifies the admin, consumes the code, and allows admin access', function () {
    $admin = makeOtpTestAdmin();
    seedAdminLoginOtp($admin);

    // Establish the intended destination before verification.
    $this->actingAs($admin)
        ->get(route('admin.home'))
        ->assertRedirect(route('admin.verify'));

    $this->get(route('admin.verify'))->assertOk();

    $this->from(route('admin.verify'))
        ->post(route('admin.verify.check'), [
            'code' => '123456',
        ])
        ->assertRedirect(route('admin.home'));

    $this->assertSame($admin->id, session('admin_verified_for'));

    $this->assertDatabaseMissing('email_otps', [
        'email' => strtolower($admin->email),
        'purpose' => EmailOtp::ADMIN_LOGIN,
    ]);

    $this->get(route('admin.home'))->assertOk();
});

test('five incorrect OTP attempts invalidate the code', function () {
    $admin = makeOtpTestAdmin();
    seedAdminLoginOtp($admin);

    foreach (range(1, 5) as $attempt) {
        $this->actingAs($admin)
            ->from(route('admin.verify'))
            ->post(route('admin.verify.check'), [
                'code' => '999999',
            ])
            ->assertRedirect(route('admin.verify'))
            ->assertSessionHasErrors('code');
    }

    $this->assertNull(session('admin_verified_for'));

    $this->assertDatabaseMissing('email_otps', [
        'email' => strtolower($admin->email),
        'purpose' => EmailOtp::ADMIN_LOGIN,
    ]);
});

test('a valid trusted device is recognized and its last-used time is refreshed', function () {
    $admin = makeOtpTestAdmin();

    seedTrustedTestDevice($admin, 'trusted-token-valid');

    $request = Request::create('/admin/home', 'GET', [], [
        AdminDevices::COOKIE => 'trusted-token-valid',
    ]);

    expect(AdminDevices::isTrusted($request, $admin))->toBeTrue();

    $this->assertDatabaseHas('admin_trusted_devices', [
        'user_id' => $admin->id,
        'token_hash' => hash('sha256', 'trusted-token-valid'),
        'last_used_at' => now()->toDateTimeString(),
    ]);
});

test('an expired trusted device is rejected', function () {
    $admin = makeOtpTestAdmin();

    seedTrustedTestDevice(
        $admin,
        'trusted-token-expired',
        now()->subMinute()
    );

    $request = Request::create('/admin/home', 'GET', [], [
        AdminDevices::COOKIE => 'trusted-token-expired',
    ]);

    expect(AdminDevices::isTrusted($request, $admin))->toBeFalse();
});

test('a trusted device belonging to another admin is rejected', function () {
    $admin = makeOtpTestAdmin();
    $otherAdmin = makeOtpTestAdmin();

    seedTrustedTestDevice($otherAdmin, 'another-admin-token');

    $request = Request::create('/admin/home', 'GET', [], [
        AdminDevices::COOKIE => 'another-admin-token',
    ]);

    expect(AdminDevices::isTrusted($request, $admin))->toBeFalse();
});

test('an expired trusted device is not identified as the current device', function () {
    $admin = makeOtpTestAdmin();

    seedTrustedTestDevice(
        $admin,
        'expired-current-device',
        now()->subMinute()
    );

    $request = Request::create('/admin/security', 'GET', [], [
        AdminDevices::COOKIE => 'expired-current-device',
    ]);

    expect(AdminDevices::currentId($request, $admin))->toBeNull();
});

test('repeated OTP verification requests are blocked by the rate limiter', function () {
    $admin = makeOtpTestAdmin();
    seedAdminLoginOtp($admin);

    $key = 'admin-code:'.$admin->id.'|127.0.0.1';
    RateLimiter::clear($key);

    foreach (range(1, 10) as $attempt) {
        $this->actingAs($admin)
            ->from(route('admin.verify'))
            ->post(route('admin.verify.check'), [
                'code' => '999999',
            ])
            ->assertRedirect(route('admin.verify'))
            ->assertSessionHasErrors('code');
    }

    expect(RateLimiter::tooManyAttempts($key, 10))->toBeTrue();

    $this->actingAs($admin)
        ->from(route('admin.verify'))
        ->post(route('admin.verify.check'), [
            'code' => '123456',
        ])
        ->assertRedirect(route('admin.verify'))
        ->assertSessionHasErrors('code');

    $messages = session('errors')->get('code');

    $this->assertStringContainsString(
        'Too many attempts',
        implode(' ', $messages)
    );

    $this->assertNull(session('admin_verified_for'));
});

test('an admin cannot revoke another admin trusted device', function () {
    $admin = makeOtpTestAdmin();
    $otherAdmin = makeOtpTestAdmin();

    $otherDeviceId = seedTrustedTestDevice(
        $otherAdmin,
        'other-admin-protected-device'
    );

    $this->actingAs($admin)
        ->withSession(['admin_verified_for' => $admin->id])
        ->from(route('admin.security'))
        ->post(route('admin.security.devices.remove', [
            'device' => $otherDeviceId,
        ]))
        ->assertRedirect(route('admin.security'));

    $this->assertDatabaseHas('admin_trusted_devices', [
        'id' => $otherDeviceId,
        'user_id' => $otherAdmin->id,
    ]);
});

test('an admin can revoke one trusted device without affecting other devices', function () {
    $admin = makeOtpTestAdmin();

    $revokedToken = 'device-to-revoke';
    $keepToken = 'device-to-keep';

    $revokedId = seedTrustedTestDevice($admin, $revokedToken);
    $keepId = seedTrustedTestDevice($admin, $keepToken);

    $this->actingAs($admin)
        ->withSession(['admin_verified_for' => $admin->id])
        ->from(route('admin.security'))
        ->post(route('admin.security.devices.remove', [
            'device' => $revokedId,
        ]))
        ->assertRedirect(route('admin.security'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('admin_trusted_devices', [
        'id' => $revokedId,
        'user_id' => $admin->id,
    ]);

    $this->assertDatabaseHas('admin_trusted_devices', [
        'id' => $keepId,
        'user_id' => $admin->id,
    ]);

    // The revoked token must no longer authenticate this browser.
    $request = Request::create(
        '/admin/home',
        'GET',
        [],
        [AdminDevices::COOKIE => $revokedToken]
    );

    expect(AdminDevices::isTrusted($request, $admin))->toBeFalse();
});

test('clearing trusted devices only removes devices belonging to the current admin', function () {
    $admin = makeOtpTestAdmin();
    $otherAdmin = makeOtpTestAdmin();

    seedTrustedTestDevice($admin, 'admin-device-one');
    seedTrustedTestDevice($admin, 'admin-device-two');

    $otherDeviceId = seedTrustedTestDevice(
        $otherAdmin,
        'other-admin-device'
    );

    $this->actingAs($admin)
        ->withSession(['admin_verified_for' => $admin->id])
        ->from(route('admin.security'))
        ->post(route('admin.security.devices.clear'))
        ->assertRedirect(route('admin.security'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('admin_trusted_devices', [
        'user_id' => $admin->id,
    ]);

    $this->assertDatabaseHas('admin_trusted_devices', [
        'id' => $otherDeviceId,
        'user_id' => $otherAdmin->id,
    ]);
});
