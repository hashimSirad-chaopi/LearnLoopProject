<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Support\AdminDevices;
use App\Support\EmailOtp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Admin > Settings. One page, four tabs:
 *   account       name and email
 *   password      change the password
 *   security      the emailed sign-in code, trusted browsers, recent sign-in events
 *   notifications which alerts also arrive by email
 */
class SettingsController extends Controller
{
    public const TABS = ['account', 'password', 'security', 'notifications'];

    /** Actions shown under "Recent sign-in activity". */
    private const SECURITY_EVENTS = [
        'admin.verified', 'admin.verify_failed', 'admin.device_removed',
        'password.reset', 'profile.password_changed', 'profile.email_changed',
    ];

    public function show(Request $request)
    {
        $user = $request->user();

        $tab = (string) $request->query('tab', old('tab', 'account'));
        if (! in_array($tab, self::TABS, true)) {
            $tab = 'account';
        }

        $devices = DB::table('admin_trusted_devices')
            ->where('user_id', $user->id)->where('expires_at', '>', now())
            ->orderByDesc('last_used_at')->get();

        $events = ActivityLog::where('user_id', $user->id)
            ->whereIn('action', self::SECURITY_EVENTS)
            ->latest()->take(10)->get();

        $lastPasswordChange = ActivityLog::where('user_id', $user->id)
            ->whereIn('action', ['password.reset', 'profile.password_changed'])
            ->latest()->first();

        $default = (bool) config('learnloop.admin_email_alerts');

        return view('admin.settings', [
            'tab' => $tab,
            'me' => $user,
            'otpEnabled' => (bool) config('learnloop.admin_email_otp', true),
            'maskedEmail' => EmailOtp::mask($user->email),
            'devices' => $devices,
            'currentDevice' => AdminDevices::currentId($request, $user),
            'events' => $events,
            'lastPasswordChange' => $lastPasswordChange?->created_at,
            'prefs' => [
                'email_report_alerts' => (bool) $user->adminPref('email_report_alerts', $default),
                'email_appeal_alerts' => (bool) $user->adminPref('email_appeal_alerts', $default),
            ],
            // With the "log" mailer, emails are written to storage/logs instead of being sent.
            'mailSends' => ! in_array(config('mail.default'), ['log', 'array'], true),
        ]);
    }

    public function updateAccount(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        $emailChanges = $data['email'] !== Str::lower($user->email);

        // The sign-in code goes to this address, so a typo would lock the admin out:
        // ask for the password and for the address twice.
        if ($emailChanges) {
            $request->validate([
                'email_confirmation' => ['required', 'same:email'],
                'current_password' => ['required', 'current_password'],
            ], [
                'email_confirmation.required' => 'Type the new email address again to confirm it.',
                'email_confirmation.same' => 'The two email addresses do not match.',
                'current_password.required' => 'Enter your password to change the email address.',
                'current_password.current_password' => 'That password is not correct.',
            ]);
        }

        $nameChanges = $data['name'] !== $user->name;

        if (! $emailChanges && ! $nameChanges) {
            return $this->back('account', 'Nothing to save: those details are already up to date.');
        }

        $old = $user->email;
        $user->forceFill(['name' => $data['name'], 'email' => $data['email']])->save();

        if ($emailChanges) {
            ActivityLog::record('profile.email_changed', "Changed own email from {$old} to {$data['email']}");
        }
        if ($nameChanges) {
            ActivityLog::record('profile.updated', 'Updated own name');
        }

        return $this->back('account', $emailChanges
            ? 'Saved. Sign-in codes now go to '.EmailOtp::mask($data['email']).'.'
            : 'Your details were saved.');
    }

    /** Profile picture or banner (same folders and limits as the member app). */
    public function savePicture(Request $request, string $kind): RedirectResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'image.required' => 'Choose a picture first.',
            'image.image' => 'That file is not a picture.',
            'image.mimes' => 'Use a JPG, PNG or WebP picture.',
            'image.max' => 'That picture is bigger than 5 MB.',
            'image.uploaded' => 'The upload failed. The picture may be bigger than the server allows.',
        ]);

        [$column, $folder, $label] = $this->pictureSlot($kind);
        $user = $request->user();

        if ($user->{$column}) {
            Storage::disk('public')->delete($user->{$column});
        }

        $user->forceFill([$column => $request->file('image')->store($folder, 'public')])->save();
        ActivityLog::record('profile.updated', "Changed own {$label}");

        return $this->back('account', ucfirst($label).' updated.');
    }

    public function removePicture(Request $request, string $kind): RedirectResponse
    {
        [$column, , $label] = $this->pictureSlot($kind);
        $user = $request->user();

        if ($user->{$column}) {
            Storage::disk('public')->delete($user->{$column});
            $user->forceFill([$column => null])->save();
            ActivityLog::record('profile.updated', "Removed own {$label}");
        }

        return $this->back('account', ucfirst($label).' removed.');
    }

    /** @return array{0:string,1:string,2:string} column, storage folder, wording */
    private function pictureSlot(string $kind): array
    {
        return $kind === 'banner'
            ? ['banner_path', 'banners', 'banner']
            : ['avatar_path', 'avatars', 'profile picture'];
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::defaults()],
        ], [
            'current_password.required' => 'Enter your current password.',
            'current_password.current_password' => 'That is not your current password.',
            'password.confirmed' => 'The two new passwords do not match.',
            'password.different' => 'Choose a password you have not used just now.',
        ]);

        $user->forceFill([
            'password' => $request->input('password'), // hashed by the model cast
            'remember_token' => Str::random(60),
        ])->save();

        // Same as a reset by email code: phones and trusted browsers must sign in again.
        $user->tokens()->delete();
        DB::table('admin_trusted_devices')->where('user_id', $user->id)->delete();

        ActivityLog::record('profile.password_changed', 'Changed own password');

        return $this->back('password', 'Password updated. Phones and trusted browsers were signed out, so the next sign-in asks for an email code.');
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        if (! Schema::hasColumn('users', 'admin_preferences')) {
            return $this->back('notifications', null, 'Run "php artisan migrate" once, then try again.');
        }

        $user = $request->user();

        $prefs = $user->admin_preferences ?? [];
        $prefs['email_report_alerts'] = $request->boolean('email_report_alerts');
        $prefs['email_appeal_alerts'] = $request->boolean('email_appeal_alerts');

        $user->forceFill(['admin_preferences' => $prefs])->save();

        return $this->back('notifications', 'Alert preferences saved.');
    }

    private function back(string $tab, ?string $success = null, ?string $error = null): RedirectResponse
    {
        $redirect = redirect()->route('admin.settings', ['tab' => $tab]);

        if ($error !== null) {
            return $redirect->withErrors(['notifications' => $error])->withInput(['tab' => $tab]);
        }

        return $redirect->with('success', $success);
    }
}
