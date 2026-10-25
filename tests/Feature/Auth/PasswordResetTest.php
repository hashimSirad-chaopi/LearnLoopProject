
<?php

use App\Models\User;
use App\Support\EmailOtp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

test('forgot password screen can be rendered', function () {
    $this->get('/forgot-password')
        ->assertOk();
});

test('reset password code can be requested', function () {
    $user = User::factory()->create();
    $email = strtolower($user->email);

    $response = $this->post(route('password.email'), [
        'email' => $email,
    ]);

    $response->assertRedirect(route('password.reset'))
        ->assertSessionHas('otp_email', $email)
        ->assertSessionHas('status');

    $this->assertDatabaseHas('email_otps', [
        'email' => $email,
        'purpose' => EmailOtp::PASSWORD_RESET,
    ]);
});

test('reset password screen can be rendered with an email in session', function () {
    $user = User::factory()->create();

    $response = $this->withSession([
        'otp_email' => strtolower($user->email),
    ])->get(route('password.reset'));

    $response->assertOk()
        ->assertViewIs('auth.reset-password');
});

test('password can be reset with a valid email code', function () {
    $user = User::factory()->create();
    $email = strtolower($user->email);
    $code = '123456';
    $newPassword = 'SecureTestPassword123!';

    // Seed a known code hash so this test does not depend on reading email.
    DB::table('email_otps')->updateOrInsert(
        [
            'email' => $email,
            'purpose' => EmailOtp::PASSWORD_RESET,
        ],
        [
            'code_hash' => hash_hmac(
                'sha256',
                $email.'|'.EmailOtp::PASSWORD_RESET.'|'.$code,
                (string) config('app.key')
            ),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(EmailOtp::TTL_MINUTES),
            'last_sent_at' => now(),
            'token_hash' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]
    );

    $response = $this->withSession([
        'otp_email' => $email,
    ])->post(route('password.store'), [
        'code' => $code,
        'password' => $newPassword,
        'password_confirmation' => $newPassword,
    ]);

    $response->assertRedirect(route('login'))
        ->assertSessionHas('status')
        ->assertSessionMissing('otp_email');

    expect(Hash::check($newPassword, $user->fresh()->password))->toBeTrue();

    $this->assertDatabaseHas('activity_logs', [
        'user_id' => $user->id,
        'action' => 'password.reset',
    ]);

    $this->assertDatabaseMissing('email_otps', [
        'email' => $email,
        'purpose' => EmailOtp::PASSWORD_RESET,
    ]);
});
