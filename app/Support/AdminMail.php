<?php

namespace App\Support;

use App\Models\AccountAppeal;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/** Emails to the administrator(s). Never lets a mail problem break the app. */
class AdminMail
{
    public static function newReport(Report $report): void
    {
        $emails = self::recipients('email_report_alerts');
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

    public static function newAppeal(AccountAppeal $appeal): void
    {
        $emails = self::recipients('email_appeal_alerts');
        if ($emails === []) {
            return;
        }

        $appeal->loadMissing('user');
        $url = url('/admin/appeals');

        $body = "A suspended member asked for their account to be reactivated.\n\n"
            .'Member: '.($appeal->user->name ?? 'Unknown')."\n\n"
            ."Message:\n{$appeal->message}\n\n"
            ."Review it here: {$url}\n";

        app()->terminating(function () use ($emails, $body, $appeal) {
            try {
                Mail::raw($body, fn ($m) => $m->to($emails)->subject('Account recovery request from '.($appeal->user->name ?? 'a member')));
            } catch (\Throwable $e) {
                Log::warning('Recovery request alert email failed: '.$e->getMessage());
            }
        });
    }

    /**
     * Active admins who want this alert by email. An admin who never touched the switch
     * follows the server default (LEARNLOOP_ADMIN_EMAIL_ALERTS).
     *
     * @return array<int, string>
     */
    private static function recipients(string $pref): array
    {
        $default = (bool) config('learnloop.admin_email_alerts');

        return User::where('role', 'admin')->where('status', 'active')->get()
            ->filter(fn (User $admin) => (bool) $admin->adminPref($pref, $default))
            ->pluck('email')->all();
    }
}
