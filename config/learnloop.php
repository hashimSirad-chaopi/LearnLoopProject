<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Email Verification
    |--------------------------------------------------------------------------
    */

    // Enabled by default. Only an explicit false disables admin email OTP.
    'admin_email_otp' => env('LEARNLOOP_ADMIN_EMAIL_OTP', true) !== false,

    /*
    |--------------------------------------------------------------------------
    | Admin Email Alerts
    |--------------------------------------------------------------------------
    */

    // Keep disabled until the mail transport is configured and tested.
    'admin_email_alerts' => (bool) env('LEARNLOOP_ADMIN_EMAIL_ALERTS', false),

    /*
    |--------------------------------------------------------------------------
    | Running behind ngrok (or any HTTPS proxy)
    |--------------------------------------------------------------------------
    */

    // Set LEARNLOOP_TRUST_PROXIES=true in .env while the site is reached through ngrok, so Laravel
    // sees the real https address and visitor IP. Leave it off when nothing sits in front of the server.
    'trust_proxies' => (bool) env('LEARNLOOP_TRUST_PROXIES', false),

    /*
    |--------------------------------------------------------------------------
    | Report Moderation
    |--------------------------------------------------------------------------
    */

    // Suspend a member after this many upheld reports. Zero disables auto-suspension.
    'auto_suspend_after' => (int) env('LEARNLOOP_AUTO_SUSPEND_AFTER', 3),

    /*
    |--------------------------------------------------------------------------
    | Quiz attempts per day
    |--------------------------------------------------------------------------
    */

    // A "day" for the daily quiz attempt limit runs midnight to midnight in this timezone.
    // The on/off switch and the number of attempts are edited in Admin > Settings > Quizzes.
    'quiz_day_timezone' => env('LEARNLOOP_QUIZ_DAY_TIMEZONE', 'Asia/Manila'),

];
