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
    | Report Moderation
    |--------------------------------------------------------------------------
    */

    // Suspend a member after this many upheld reports. Zero disables auto-suspension.
    'auto_suspend_after' => (int) env('LEARNLOOP_AUTO_SUSPEND_AFTER', 3),

];
