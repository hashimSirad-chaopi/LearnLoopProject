<?php

namespace App\Support;

use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/** Emails to the administrator(s). Never lets a mail problem break the app. */
class AdminMail
{
    public static function newReport(Report $report): void
    {
        if (! config('learnloop.admin_email_alerts')) {
            return;
        }

        $emails = User::where('role', 'admin')->where('status', 'active')->pluck('email')->all();
        if ($emails === []) {
            return;
        }

        $report->loadMissing('reporter', 'reportedUser');
        $url = url('/admin/reports');

        $body = "A new report was filed on LearnLoop.\n\n"
            ."Title: {$report->title}\n"
            .'Reported by: '.($report->reporter->name ?? 'Unknown')."\n"
            .'About: '.($report->reportedUser->name ?? 'Unknown')."\n\n"
            ."Reason:\n{$report->reason}\n\n"
            ."Review it here: {$url}\n";

        // After the response is sent, so the member filing the report never waits for email.
        app()->terminating(function () use ($emails, $body, $report) {
            try {
                Mail::raw($body, fn ($m) => $m->to($emails)->subject("New report: {$report->title}"));
            } catch (\Throwable $e) {
                Log::warning('Report alert email failed: '.$e->getMessage());
            }
        });
    }
}
