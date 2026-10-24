<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\Report;
use App\Models\User;

/**
 * What happens when the (single) admin decides a report.
 *
 *   resolve  = the report was right.  Optionally warn or suspend the member.
 *   dismiss  = no rule was broken.    Only the reporter is told.
 *
 * Privacy: the reported member is never told WHO reported them.
 */
class Moderation
{
    public const ACTIONS = ['none', 'warn', 'suspend'];

    /** @return string a sentence for the admin describing what happened */
    public static function resolve(Report $report, string $action = 'none', ?string $note = null, string $duration = Suspension::DEFAULT, string $reason = Suspension::DEFAULT_REASON): string
    {
        $member = $report->reportedUser;

        // Never warn or suspend an administrator through a report.
        if (! $member || $member->role === 'admin') {
            $action = 'none';
        }
        if (! in_array($action, self::ACTIONS, true)) {
            $action = 'none';
        }

        $note = $note !== null ? trim($note) : null;

        $report->forceFill([
            'status' => 'resolved',
            'action_taken' => $action,
            'admin_note' => $note !== '' ? $note : null,
            'resolved_at' => now(),
        ])->save();

        ActivityLog::record('report.resolved', "Resolved report \"{$report->title}\"".($action !== 'none' ? " ({$action})" : ''));

        // The reporter always hears back.
        if ($report->reported_by) {
            Notification::notifyMember($report->reported_by, 'Report resolved', "An admin reviewed your report \"{$report->title}\" and took action.", 'report', $report->id);
        }

        $result = 'Report resolved.';

        if ($member && $action === 'warn') {
            Notification::notifyMember(
                $member->id,
                'Warning from admin',
                'A report about your account or listings was reviewed and upheld. Please follow the LearnLoop community guidelines.'.($report->admin_note ? " Note from admin: {$report->admin_note}" : ''),
                'user',
                $member->id
            );
            ActivityLog::record('user.warned', "Warned {$member->name} (report \"{$report->title}\")");
            $result = "Report resolved and {$member->name} was sent a warning.";
        }

        if ($member && $action === 'suspend') {
            self::suspend($member, 'Account suspended', 'An administrator suspended your account {when} after a report was upheld.', 'user.suspended', "Suspended {$member->name} {when} (report \"{$report->title}\")", $duration, $reason, $report->admin_note);
            $result = "Report resolved and {$member->name} was suspended.";
        }

        // Safety net: too many upheld reports suspends the account automatically.
        if ($member && $action !== 'suspend' && self::shouldAutoSuspend($member)) {
            $count = Report::upheldAgainst($member->id);
            self::suspend(
                $member,
                'Account suspended',
                "Your account was suspended automatically after {$count} reports were upheld.",
                'user.auto_suspended',
                "Auto-suspended {$member->name} after {$count} upheld reports",
                Suspension::DEFAULT,
                Suspension::REASON_REPORTS,
                null,
                'If you think this is a mistake, send the admins a recovery request from the login screen.'
            );
            $result = "Report resolved. {$member->name} reached {$count} upheld reports and was suspended automatically.";
        }

        return $result;
    }

    public static function dismiss(Report $report): void
    {
        $report->forceFill(['status' => 'dismissed', 'resolved_at' => now()])->save();

        ActivityLog::record('report.dismissed', "Dismissed report \"{$report->title}\"");

        if ($report->reported_by) {
            Notification::notifyMember($report->reported_by, 'Report reviewed', "An admin reviewed your report \"{$report->title}\" and found no action was needed.", 'report', $report->id);
        }
    }

    private static function shouldAutoSuspend(User $member): bool
    {
        $limit = (int) config('learnloop.auto_suspend_after', 3);

        return $limit > 0
            && $member->role !== 'admin'
            && $member->status === 'active'
            && Report::upheldAgainst($member->id) >= $limit;
    }

    /**
     * "{when}" in the message / log text becomes "until Oct 25, 2026 3:42 PM UTC" or
     * "until an administrator reactivates it". The reason is added after the message.
     */
    private static function suspend(
        User $member,
        string $title,
        string $message,
        string $logAction,
        string $logText,
        string $duration = Suspension::DEFAULT,
        string $reason = Suspension::DEFAULT_REASON,
        ?string $note = null,
        string $suffix = ''
    ): void {
        $when = Suspension::apply($member, $duration, $reason, $note);
        $why = Suspension::describe($reason, $note) ?? Suspension::reasonLabel($reason);

        ActivityLog::record($logAction, str_replace('{when}', $when, $logText).". Reason: {$why}");
        Notification::notifyMember(
            $member->id,
            $title,
            trim(str_replace('{when}', $when, $message)." Reason: {$why}. {$suffix}"),
            'user',
            $member->id
        );
    }
}
