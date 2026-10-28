<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:24px;background:#f1f5ef;font-family:Arial,Helvetica,sans-serif;color:#16231c;">
    <div style="max-width:460px;margin:0 auto;background:#ffffff;border:1px solid #d9e3d6;border-radius:16px;padding:28px;">
        <div style="font-weight:900;letter-spacing:2px;color:#16231c;font-size:18px;">LEARN LOOP</div>
        <h2 style="margin:18px 0 6px;font-size:20px;">
            {{ ['admin_login' => 'Admin sign-in code', 'register' => 'Verify your email'][$purpose] ?? 'Reset your password' }}
        </h2>
        <p style="margin:0 0 18px;font-size:14px;line-height:1.5;color:#4b5a50;">
            Hi {{ $name ?: 'there' }},
            {{ $purpose === 'admin_login'
                ? 'someone just signed in to the LearnLoop admin panel with your password. Enter this code to finish.'
                : ($purpose === 'register'
                    ? 'welcome to LearnLoop! Enter this code in the app to confirm this email and finish creating your account.'
                    : 'use this code to choose a new password.') }}
        </p>
        <div style="text-align:center;font-size:34px;font-weight:800;letter-spacing:10px;background:#e6f4e4;border:1px solid #b9dcb4;border-radius:12px;padding:16px 0;">
            {{ $code }}
        </div>
        <p style="margin:18px 0 0;font-size:13px;line-height:1.5;color:#4b5a50;">
            It works for {{ $minutes }} minutes and only once. Never share it with anyone, including LearnLoop staff.
            @if($purpose === 'admin_login')
                <strong>If this was not you, change your password now.</strong>
            @elseif($purpose === 'register')
                If you did not try to create a LearnLoop account, ignore this email: nothing was created.
            @else
                If you did not ask for this, ignore this email: your password stays the same.
            @endif
        </p>
    </div>
</body>
</html>
