<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Report;
use App\Models\Listing;
use App\Models\Exchange;
use Illuminate\Support\Facades\Hash;
use App\Models\Category;
use App\Models\AccountAppeal;
use App\Models\ActivityLog;
use App\Models\Lesson;
use App\Models\Notification;
use App\Models\Skill;
use App\Support\AuthorTrust;
use App\Support\StorageCleanup;
use App\Support\Suspension;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class PageController extends Controller
{
    public function adminHome()
{
    User::liftExpiredSuspensions();

    $days = (int) request('range', 30);

    return view('admin.home', [
        'stats' => \App\Support\DashboardStats::build($days),
        'attention' => \App\Support\AdminAlerts::items(),
        'attentionTotal' => \App\Support\AdminAlerts::total(),
    ]);
}

/** Dashboard numbers as a CSV file (opens in Excel / Google Sheets). */
public function exportDashboard(Request $request)
{
    $stats = \App\Support\DashboardStats::build((int) $request->query('range', 30));
    $days = $stats['days'];

    // Spreadsheet apps run text that starts with = + - @ as a formula. Defuse it.
    $safe = fn ($v) => is_string($v) && $v !== '' && in_array($v[0], ['=', '+', '-', '@'], true) ? "'".$v : $v;

    return response()->streamDownload(function () use ($stats, $days, $safe) {
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // so Excel reads UTF-8 correctly

        fputcsv($out, ['LearnLoop dashboard export', now()->format('Y-m-d H:i'), "Range: last {$days} days"]);
        fputcsv($out, []);

        fputcsv($out, ['SUMMARY', 'Value']);
        foreach ([
            ['Members', $stats['totalMembers']],
            ["New members (last {$days} days)", $stats['newMembersRange']],
            ['Active listings', $stats['activeListings']],
            ['Total listings', $stats['totalListings']],
            ['Total exchanges', $stats['totalExchanges']],
            ['Average rating', $stats['avgRating'] ?? ''],
            ['Ratings', $stats['ratingsCount']],
            ['Skills', $stats['skillCount']],
            ['Categories', $stats['categoryCount']],
            ['Published lessons', $stats['publishedLessons']],
            ['Open reports', $stats['pendingReports']],
            ['Members online now', $stats['onlineNow']],
            ['Suspended members', $stats['suspendedMembers']],
            ["Comments (last {$days} days)", $stats['commentsRange']],
            ['Comments (all time)', $stats['commentsTotal']],
            ["New listings (last {$days} days)", $stats['newListingsRange']],
            ["New members in the {$days} days before", $stats['newMembersPrevious']],
        ] as $row) {
            fputcsv($out, $row);
        }

        fputcsv($out, []);
        fputcsv($out, ['EXCHANGES BY STATUS', 'Count']);
        foreach ($stats['exchangeStatuses'] as $status => $count) {
            fputcsv($out, [ucfirst($status), $count]);
        }

        fputcsv($out, []);
        fputcsv($out, ['NEW MEMBERS', 'Count']);
        foreach ($stats['signups'] as $row) {
            fputcsv($out, [$row['label'], $row['count']]);
        }

        fputcsv($out, []);
        fputcsv($out, ['WEEK STARTING', 'Lessons passed', 'Exchanges completed']);
        foreach ($stats['engagement']['weeks'] as $row) {
            fputcsv($out, [$row['label'], $row['lessons'], $row['exchanges']]);
        }

        foreach ([['MOST WANTED SKILLS', $stats['topWanted']], ['MOST OFFERED SKILLS', $stats['topOffered']], ['TOP CATEGORIES (LISTINGS)', $stats['topCategories']]] as [$title, $rows]) {
            fputcsv($out, []);
            fputcsv($out, [$title, 'Count']);
            foreach ($rows as $row) {
                fputcsv($out, [$safe($row['name']), $row['count']]);
            }
        }

        fclose($out);
    }, 'learnloop-dashboard-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
}

   public function adminUsers(Request $request)
{
    User::liftExpiredSuspensions();

    $search = trim((string) $request->query('search', ''));
    $status = in_array($request->query('status'), ['active', 'offline', 'suspended'], true) ? $request->query('status') : 'all';

    $columns = ['name', 'email', 'role', 'status', 'created_at'];
    $sort = in_array($request->query('sort'), $columns, true) ? $request->query('sort') : 'created_at';
    $dir = $request->query('dir') === 'asc' ? 'asc' : 'desc';

    $users = $this->usersQuery($search, $status)
        ->orderBy($sort, $dir)
        ->paginate(15)
        ->withQueryString();

    // Counts for just the rows on this page, in two queries (not one per user).
    $ids = $users->getCollection()->pluck('id');
    $listingCounts = Listing::whereIn('user_id', $ids)
        ->selectRaw('user_id, COUNT(*) as total')->groupBy('user_id')->pluck('total', 'user_id');
    $reportCounts = Report::whereIn('reported_user_id', $ids)->where('status', 'resolved')
        ->selectRaw('reported_user_id as uid, COUNT(*) as total')->groupBy('reported_user_id')->pluck('total', 'uid');

    $counts = [
        'all' => User::count(),
        'active' => User::online()->count(),
        'offline' => User::offline()->count(),
        'suspended' => User::where('status', 'suspended')->count(),
        'week' => User::where('created_at', '>=', now()->subDays(7))->count(),
    ];

    return view('admin.users', compact('users', 'search', 'status', 'sort', 'dir', 'listingCounts', 'reportCounts', 'counts'));
}

/** The members matching the Users page filters (shared by the list and the CSV export). */
private function usersQuery(string $search, string $status)
{
    return User::when($search !== '', function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        })
        // active = not suspended and seen in the last 2 minutes; offline = not suspended and not seen; suspended = suspended.
        ->when($status === 'active', fn ($query) => $query->online())
        ->when($status === 'offline', fn ($query) => $query->offline())
        ->when($status === 'suspended', fn ($query) => $query->where('status', 'suspended'));
}

/** The Users page as a CSV file (same search and status filter), for Excel / Google Sheets. */
public function exportUsers(Request $request)
{
    $search = trim((string) $request->query('search', ''));
    $status = in_array($request->query('status'), ['active', 'offline', 'suspended'], true) ? $request->query('status') : 'all';

    // Spreadsheet apps run text that starts with = + - @ as a formula. Defuse it.
    $safe = fn ($v) => is_string($v) && $v !== '' && in_array($v[0], ['=', '+', '-', '@'], true) ? "'".$v : $v;

    ActivityLog::record('users.exported', 'Exported the users list'.($search !== '' ? " (search: {$search})" : '').($status !== 'all' ? " (status: {$status})" : ''));

    return response()->streamDownload(function () use ($search, $status, $safe) {
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // so Excel reads UTF-8 correctly
        fputcsv($out, ['ID', 'Name', 'Email', 'Role', 'Status', 'Joined', 'Last seen', 'Listings', 'Upheld reports']);

        $this->usersQuery($search, $status)->orderBy('id')->chunkById(200, function ($users) use ($out, $safe) {
            $ids = $users->pluck('id');
            $listings = Listing::whereIn('user_id', $ids)->selectRaw('user_id, COUNT(*) as total')->groupBy('user_id')->pluck('total', 'user_id');
            $reports = Report::whereIn('reported_user_id', $ids)->where('status', 'resolved')->selectRaw('reported_user_id as uid, COUNT(*) as total')->groupBy('reported_user_id')->pluck('total', 'uid');

            foreach ($users as $user) {
                fputcsv($out, [
                    $user->id,
                    $safe($user->name),
                    $safe($user->email),
                    $user->role,
                    $user->status,
                    $user->created_at?->format('Y-m-d'),
                    $user->last_seen_at?->format('Y-m-d H:i'),
                    (int) ($listings[$user->id] ?? 0),
                    (int) ($reports[$user->id] ?? 0),
                ]);
            }
        });

        fclose($out);
    }, 'learnloop-users-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
}

/** Suspend or reactivate several members at once. Admins and yourself are always skipped. */
public function bulkUsers(Request $request)
{
    $data = $request->validate([
        'ids' => 'required|array|min:1',
        'ids.*' => 'integer|exists:users,id',
        'bulk' => 'required|in:suspend,activate',
        'duration' => 'nullable|in:'.implode(',', Suspension::keys()),
        'reason' => 'nullable|in:'.implode(',', Suspension::reasonKeys()),
        'note' => [\Illuminate\Validation\Rule::requiredIf($request->input('bulk') === 'suspend' && $request->input('reason') === 'other'), 'nullable', 'string', 'max:500'],
    ]);

    $reason = $data['reason'] ?? Suspension::DEFAULT_REASON;

    $users = User::whereIn('id', $data['ids'])
        ->where('role', '!=', 'admin')
        ->where('id', '!=', auth()->id())
        ->get();

    $changed = 0;
    foreach ($users as $user) {
        if ($data['bulk'] === 'suspend' && $user->status !== 'suspended') {
            $when = Suspension::apply($user, $data['duration'] ?? null, $reason, $data['note'] ?? null);
            $why = Suspension::describe($reason, $data['note'] ?? null);
            ActivityLog::record('user.suspended', "Suspended user {$user->name} {$when}. Reason: {$why} (bulk)");
            Notification::notifyMember($user->id, 'Account suspended', "An administrator suspended your account {$when}. Reason: {$why}.", 'user', $user->id);
            $changed++;
        } elseif ($data['bulk'] === 'activate' && $user->status !== 'active') {
            $user->status = 'active';
            Suspension::clear($user);
            $user->save();
            ActivityLog::record('user.activated', "Reactivated user {$user->name} (bulk)");
            AccountAppeal::closeOpenFor($user->id);
            Notification::notifyMember($user->id, 'Account reactivated', 'An administrator reactivated your account.', 'user', $user->id);
            $changed++;
        }
    }

    $skipped = count($data['ids']) - $changed;

    return back()->with('success', $changed.' '.\Illuminate\Support\Str::plural('member', $changed).' '.($data['bulk'] === 'suspend' ? 'suspended' : 'reactivated').'.'
        .($skipped > 0 ? " {$skipped} skipped (admins, yourself, or already in that state)." : ''));
}


    public function adminListings(Request $request)
{
    $search = $request->query('search');

    $listings = Listing::with(['user', 'category', 'offersSkill', 'wantsSkill'])
    ->when($search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhereHas('offersSkill', fn ($s) => $s->where('title', 'like', "%{$search}%"))
              ->orWhereHas('wantsSkill', fn ($s) => $s->where('title', 'like', "%{$search}%"))
              ->orWhereHas('category', function ($categoryQuery) use ($search) {
                  $categoryQuery->where('name', 'like', "%{$search}%");
              });
        });
    })
    ->when(in_array($request->query('status'), ['active', 'pending', 'inactive'], true), fn ($q) => $q->where('status', $request->query('status')))
    ->when(true, function ($q) use ($request) {
        match ($request->query('sort')) {
            'oldest' => $q->oldest(),
            'title' => $q->orderBy('title'),
            'status' => $q->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'active' THEN 1 ELSE 2 END")->latest(),
            default => $q->latest(),
        };
    })
    ->paginate(15)
    ->withQueryString();

    $status = in_array($request->query('status'), ['active', 'pending', 'inactive'], true) ? $request->query('status') : 'all';
    $sort = in_array($request->query('sort'), ['oldest', 'title', 'status'], true) ? $request->query('sort') : 'newest';

    return view('admin.listing', compact('listings', 'search', 'status', 'sort'));
}
    

    /** Approve / enable / disable several listings at once. */
    public function bulkListings(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:listings,id',
            'bulk' => 'required|in:enable,disable',
        ]);

        $status = $data['bulk'] === 'enable' ? 'active' : 'inactive';
        $listings = Listing::whereIn('id', $data['ids'])->where('status', '!=', $status)->get();

        foreach ($listings as $listing) {
            $listing->update(['status' => $status]);
            ActivityLog::record($status === 'active' ? 'listing.enabled' : 'listing.disabled', ($status === 'active' ? 'Enabled' : 'Disabled')." listing \"{$listing->title}\" (bulk)");

            if ($status === 'active') {
                Notification::notifyMember($listing->user_id, 'Listing approved', "Your listing \"{$listing->title}\" is now live.", 'listing', $listing->id);
            }
        }

        return back()->with('success', $listings->count().' '.\Illuminate\Support\Str::plural('listing', $listings->count()).' '.($status === 'active' ? 'enabled' : 'disabled').'.');
    }

    public function adminSettings()
    {
        return view('admin.settings');

    }
   public function deleteUser(User $user)
{
    if ($user->id === auth()->id()) {
        return back()->withErrors(['delete' => "You can't delete your own account."]);
    }

    if ($user->role === 'admin' && $user->status === 'active' && User::where('role', 'admin')->where('status', 'active')->count() <= 1) {
    return back()->withErrors(['delete' => 'Cannot delete the last active admin.']);
    }

    // The database removes the member's rows by itself, but not their uploaded files.
    // Collect the file paths first, delete in one transaction, then remove the files.
    $paths = [];
    DB::transaction(function () use ($user, &$paths) {
        $paths = StorageCleanup::forUser($user);
        $user->tokens()->delete();
        $user->delete();
    });
    StorageCleanup::delete($paths);

    ActivityLog::record('user.deleted', "Deleted user {$user->name} ({$user->email})");


    return redirect()->route('admin.users')->with('success', 'User deleted successfully.');
}

public function suspendUser(Request $request, User $user)
{
    $data = $request->validate([
        'duration' => 'nullable|in:'.implode(',', Suspension::keys()),
        'reason' => 'required|in:'.implode(',', Suspension::reasonKeys()),
        // "Other" has to be explained in the member's own words.
        'note' => [\Illuminate\Validation\Rule::requiredIf($request->input('reason') === 'other'), 'nullable', 'string', 'max:500'],
    ]);

    if ($user->id === auth()->id()) {
        return back()->withErrors([
            'status' => "You can't suspend your own account."
        ]);
    }

    if ($user->role === 'admin' && User::where('role', 'admin')->where('status', 'active')->count() <= 1) {
        return back()->withErrors([
            'status' => 'Cannot suspend the last active admin.'
        ]);
    }

    $when = Suspension::apply($user, $data['duration'] ?? null, $data['reason'], $data['note'] ?? null);
    $why = Suspension::describe($data['reason'], $data['note'] ?? null);

    ActivityLog::record('user.suspended', "Suspended user {$user->name} {$when}. Reason: {$why}");

    Notification::notifyMember($user->id, 'Account suspended', "An administrator suspended your account {$when}. Reason: {$why}.", 'user', $user->id);

    return back()->with('success', "{$user->name} was suspended {$when}.");
}

public function activateUser(User $user)
{
    $user->status = 'active';
    Suspension::clear($user);
    $user->save();

    ActivityLog::record('user.activated', "Reactivated user {$user->name}");
    AccountAppeal::closeOpenFor($user->id);

    Notification::notifyMember($user->id, 'Account reactivated', 'An administrator reactivated your account.', 'user', $user->id);

    return back()->with('success', 'User reactivated successfully.');
}

public function adminActivity(Request $request)
{
    // Filter groups: the label shown in the menu => the action prefixes it covers.
    $types = [
        'user' => ['Members', ['user.', 'users.']],
        'listing' => ['Listings', ['listing.']],
        'report' => ['Reports', ['report.']],
        'exchange' => ['Exchanges', ['exchange.']],
        'lesson' => ['Lessons', ['lesson.', 'professional.']],
        'catalog' => ['Skills and categories', ['skill.', 'category.']],
        'security' => ['Sign-in and security', ['admin.', 'profile.']],
    ];

    $search = trim((string) $request->query('search', ''));
    $type = array_key_exists((string) $request->query('type'), $types) ? (string) $request->query('type') : '';

    $logs = ActivityLog::with('admin')
        ->when($type !== '', fn ($q) => $q->where(function ($w) use ($types, $type) {
            foreach ($types[$type][1] as $prefix) {
                $w->orWhere('action', 'like', $prefix.'%');
            }
        }))
        ->when($search !== '', fn ($q) => $q->where(function ($w) use ($search) {
            $w->where('description', 'like', "%{$search}%")->orWhere('action', 'like', "%{$search}%");
        }))
        ->latest()
        ->paginate(20)
        ->withQueryString();

    return view('admin.activity', [
        'logs' => $logs,
        'search' => $search,
        'type' => $type,
        'types' => collect($types)->map(fn ($t) => $t[0])->all(),
    ]);
}
public function adminReports(Request $request)
{
    $search = trim((string) $request->query('search', ''));
    $statusFilter = in_array($request->query('status'), ['pending', 'resolved', 'dismissed'], true) ? $request->query('status') : 'all';

    // The numbers on the status cards (always the totals, whatever is searched).
    $byStatus = Report::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
    $counts = [
        'all' => (int) $byStatus->sum(),
        'pending' => (int) ($byStatus['pending'] ?? 0),
        'resolved' => (int) ($byStatus['resolved'] ?? 0),
        'dismissed' => (int) ($byStatus['dismissed'] ?? 0),
    ];

    $reports = Report::with(['reporter', 'reportedUser', 'listing'])
        ->when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        })
        ->when($statusFilter && $statusFilter !== 'all', function ($query) use ($statusFilter) {
            $query->where('status', $statusFilter);
        })
        // Reports waiting for you come first, then newest first.
        ->orderByRaw("case status when 'pending' then 0 else 1 end")
        ->latest()->paginate(15)->withQueryString();

    // How many reports an admin already upheld / warned about, per reported member.
    $ids = $reports->pluck('reported_user_id')->filter()->unique();
    $upheld = Report::where('status', 'resolved')->whereIn('reported_user_id', $ids)
        ->selectRaw('reported_user_id as uid, COUNT(*) as total')->groupBy('reported_user_id')->pluck('total', 'uid');
    $warnings = Report::where('action_taken', 'warn')->whereIn('reported_user_id', $ids)
        ->selectRaw('reported_user_id as uid, COUNT(*) as total')->groupBy('reported_user_id')->pluck('total', 'uid');
    $autoSuspendAfter = (int) config('learnloop.auto_suspend_after', 3);

    return view('admin.reports', compact('reports', 'search', 'statusFilter', 'counts', 'upheld', 'warnings', 'autoSuspendAfter'));
}

public function resolveReport(Request $request, Report $report)
{
    if ($report->status !== 'pending') {
        return back()->withErrors(['report' => 'This report was already reviewed.']);
    }

    $data = $request->validate([
        'action' => 'nullable|in:none,warn,suspend',
        'note' => 'nullable|string|max:500',
        'duration' => 'nullable|in:'.implode(',', Suspension::keys()),
        'reason' => 'nullable|in:'.implode(',', Suspension::reasonKeys()),
    ]);

    // "Other" has no wording of its own here: the note below is the reason.
    if (($data['action'] ?? 'none') === 'suspend' && ($data['reason'] ?? null) === 'other' && trim((string) ($data['note'] ?? '')) === '') {
        return back()->withErrors(['note' => 'Write the reason in the note when you choose "Other".'])->withInput();
    }

    $message = \App\Support\Moderation::resolve($report, $data['action'] ?? 'none', $data['note'] ?? null, $data['duration'] ?? Suspension::DEFAULT, $data['reason'] ?? Suspension::DEFAULT_REASON);

    return back()->with('success', $message);
}

public function dismissReport(Report $report)
{
    if ($report->status !== 'pending') {
        return back()->withErrors(['report' => 'This report was already reviewed.']);
    }

    \App\Support\Moderation::dismiss($report);

    return back()->with('success', 'Report dismissed.');
}

/** Resolve or dismiss several reports at once (no warning/suspension in bulk, on purpose). */
public function bulkReports(Request $request)
{
    $data = $request->validate([
        'ids' => 'required|array|min:1',
        'ids.*' => 'integer|exists:reports,id',
        'bulk' => 'required|in:resolve,dismiss',
    ]);

    $reports = Report::with('reportedUser')->whereIn('id', $data['ids'])->where('status', 'pending')->get();

    foreach ($reports as $report) {
        $data['bulk'] === 'resolve'
            ? \App\Support\Moderation::resolve($report, 'none')
            : \App\Support\Moderation::dismiss($report);
    }

    return back()->with('success', $reports->count().' '.\Illuminate\Support\Str::plural('report', $reports->count()).' '.($data['bulk'] === 'resolve' ? 'resolved' : 'dismissed').'.');
}

public function createUser()
{
    return view('admin.users-create');
}

public function storeUser(Request $request)
{
    // Emails are compared case-sensitively by SQLite, so "A@x.com" and "a@x.com" would be two accounts.
    $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:users,email',
        'password' => ['required', 'string', Password::min(8)->letters()->numbers()],
        'role' => 'required|in:admin,user',
    ]);

    User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),
        'role' => $validated['role'],
    ]);

    ActivityLog::record('user.created', "Created user {$validated['name']} ({$validated['role']})");

    return redirect()->route('admin.users')->with('success', 'User created successfully.');
}

public function showUser(User $user)
{
    $user->load(['userSkills.skill']);

    $skillsOffered = $user->userSkills->where('type', 'offered');
    $skillsWanted = $user->userSkills->where('type', 'wanted');

    // Trusted lesson author, per skill (3 approved lessons + 5 ratings averaging 4.0+).
    $authorTrust = Skill::whereHas('lessons', fn ($q) => $q->where('lessons.author_id', $user->id))
        ->get()
        ->map(fn (Skill $skill) => ['skill' => $skill] + AuthorTrust::statusFor($user->id, $skill->id));

    // Skills the member has started (passed at least one quiz) or is verifying as Professional.
    // Also lists the skills they picked to learn or teach, even before the first lesson.
    $pickedIds = $user->userSkills->pluck('skill_id')->unique();
    $learning = \App\Support\TeachingLevels::all($user->id)
        ->filter(fn ($row) => $row['lessons_passed'] > 0 || $row['professional'] !== null
            || ($row['lessons_total'] > 0 && $pickedIds->contains($row['skill_id'])))
        ->values();

    // Moderation picture: how much they post, and what has been reported or done about them.
    $stats = [
        'listings' => Listing::where('user_id', $user->id)->count(),
        'comments' => \App\Models\ListingComment::where('user_id', $user->id)->count(),
        'exchanges' => Exchange::where(fn ($q) => $q->where('provider_id', $user->id)->orWhere('learner_id', $user->id))->count(),
        'reports_against' => Report::where('reported_user_id', $user->id)->count(),
        'reports_upheld' => Report::upheldAgainst($user->id),
        'warnings' => Report::warningsAgainst($user->id),
        'reports_filed' => Report::where('reported_by', $user->id)->count(),
    ];

    $recentListings = Listing::where('user_id', $user->id)->latest()->limit(5)->get();
    $recentComments = \App\Models\ListingComment::with('listing:id,title')->where('user_id', $user->id)->latest()->limit(5)->get();
    $recentReports = Report::with('reporter:id,name')->where('reported_user_id', $user->id)->latest()->limit(5)->get();

    // Suspensions and reactivations of this member, newest first (the log records them by name).
    $history = ActivityLog::with('admin:id,name')
        ->whereIn('action', ['user.suspended', 'user.activated'])
        ->where('description', 'like', '%'.$user->name.'%')
        ->latest()
        ->limit(8)
        ->get();

    return view('admin.users-show', [
        'stats' => $stats,
        'recentListings' => $recentListings,
        'recentComments' => $recentComments,
        'recentReports' => $recentReports,
        'history' => $history,
        'user' => $user,
        'learning' => $learning,
        'skillsOffered' => $skillsOffered,
        'skillsWanted' => $skillsWanted,
        'wantedIds' => $skillsWanted->pluck('skill_id')->all(),
        'offeredIds' => $skillsOffered->pluck('skill_id')->all(),
        'authorTrust' => $authorTrust,
        'lessonsWritten' => $user->authoredLessons()->count(),
        'completedExchanges' => $user->completedExchangesCount(),
        'ratingAverage' => round($user->average_rating, 1),
        'ratingCount' => $user->ratingsReceived()->count(),
    ]);
}

public function editUser(User $user)
{
    return view('admin.users-edit', compact('user'));
}

public function updateUser(Request $request, User $user)
{
    $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:users,email,' . $user->id,
        'role' => 'required|in:admin,user',
        'password' => ['nullable', 'string', Password::min(8)->letters()->numbers()],
    ]);

    $wasAdmin = $user->role === 'admin';

    if ($wasAdmin && $user->status === 'active' && $validated['role'] !== 'admin' && User::where('role', 'admin')->where('status', 'active')->count() <= 1) {
    return back()->withErrors(['role' => 'Cannot demote the last active admin.']);
    }

    $oldRole = $user->role;

    $user->name = $validated['name'];
    $user->email = $validated['email'];
    $user->role = $validated['role'];

    $passwordChanged = !empty($validated['password']);
    if ($passwordChanged) {
        $user->password = Hash::make($validated['password']);
    }

    $user->save();

    // A new password signs the member out of every phone that still holds the old one.
    if ($passwordChanged) {
        $user->tokens()->delete();
    }

    ActivityLog::record('user.updated', "Updated user {$user->name}"
        .($oldRole !== $user->role ? " (role: {$oldRole} to {$user->role})" : '')
        .($passwordChanged ? ' (password reset)' : ''));

    return redirect()->route('admin.users')->with('success', 'User updated successfully.');
}

public function createListing()
{
    return view('admin.listings-create', [
        'users' => User::where('role', 'user')->where('status', 'active')->orderBy('name')->get(),
        'skills' => Skill::where('status', 'active')->orderBy('title')->get(),
    ]);
}

public function storeListing(Request $request)
{
    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
        'title' => 'required|string|max:255',
        // Same shape as the app: the member picks the skill they WANT (always) and the skill
        // they OFFER. Offers left empty = a learner-only listing. The category is taken from
        // the skill (see Listing::booted), so it is only needed for old listings without skills.
        'wants_skill_id' => 'required|exists:skills,id',
        'offers_skill_id' => 'nullable|exists:skills,id|different:wants_skill_id',
        'category_id' => 'nullable|exists:categories,id',
        'description' => 'required|string',
        'status' => 'required|in:active,pending,inactive',
    ]);

    Listing::create($validated);

    ActivityLog::record('listing.created', "Created listing \"{$validated['title']}\"");

    return redirect()->route('admin.listings')->with('success', 'Listing created successfully.');
}

public function editListing(Listing $listing)
{
    return view('admin.listings-edit', [
        'listing' => $listing,
        // Members only, but always keep the current owner in the list so saving never changes it by accident.
        'users' => User::where(fn ($q) => $q->where('role', 'user')->orWhere('id', $listing->user_id))->orderBy('name')->get(),
        'skills' => Skill::where('status', 'active')->orderBy('title')->get(),
    ]);
}

public function updateListing(Request $request, Listing $listing)
{
    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
        'title' => 'required|string|max:255',
        // Same shape as the app: the member picks the skill they WANT (always) and the skill
        // they OFFER. Offers left empty = a learner-only listing. The category is taken from
        // the skill (see Listing::booted), so it is only needed for old listings without skills.
        'wants_skill_id' => 'required|exists:skills,id',
        'offers_skill_id' => 'nullable|exists:skills,id|different:wants_skill_id',
        'category_id' => 'nullable|exists:categories,id',
        'description' => 'required|string',
        'status' => 'required|in:active,pending,inactive',
    ]);

    $listing->update($validated);

    ActivityLog::record('listing.updated', "Updated listing \"{$listing->title}\"");

    return redirect()->route('admin.listings')->with('success', 'Listing updated successfully.');
}

public function disableListing(Listing $listing)
{
    $listing->status = 'inactive';
    $listing->save();

    ActivityLog::record('listing.disabled', "Disabled listing \"{$listing->title}\"");

    Notification::notifyMember($listing->user_id, 'Listing disabled', "An admin disabled your listing \"{$listing->title}\". It is no longer visible in the feed.", 'listing', $listing->id);

    return back()->with('success', 'Listing disabled.');
}

public function deleteListing(Listing $listing)
{
    $listing->delete();

    ActivityLog::record('listing.deleted', "Deleted listing \"{$listing->title}\"");

    Notification::notifyMember($listing->user_id, 'Listing removed', "An admin removed your listing \"{$listing->title}\".", 'listing', null);

    return back()->with('success', 'Listing deleted.');
}

public function enableListing(Listing $listing)
{
    $listing->status = 'active';
    $listing->save();

    ActivityLog::record('listing.enabled', "Enabled listing \"{$listing->title}\"");

    Notification::notifyMember($listing->user_id, 'Listing approved', "Your listing \"{$listing->title}\" was approved and is now visible in the feed.", 'listing', $listing->id);

    return back()->with('success', 'Listing enabled.');
}

public function deleteReport(Report $report)
{
    $report->delete();

    ActivityLog::record('report.deleted', "Deleted report \"{$report->title}\"");

    return back()->with('success', 'Report deleted.');
}

public function createReport()
{
    $users = User::where('role', 'user')->orderBy('name')->get();
    $listings = Listing::orderBy('title')->get(['id', 'title']);

    return view('admin.reports-create', compact('users', 'listings'));
}

public function storeReport(Request $request)
{
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'reported_by' => 'required|exists:users,id',
        'reported_user_id' => 'required|exists:users,id|different:reported_by',
        'listing_id' => 'nullable|exists:listings,id',
        'reason' => 'required|string',
        'status' => 'required|in:pending,resolved,dismissed',
    ]);

    $report = Report::create($validated);
    \App\Support\AdminMail::newReport($report);

    ActivityLog::record('report.created', "Filed report \"{$validated['title']}\"");

    return redirect()->route('admin.reports')->with('success', 'Report created successfully.');
}

public function adminExchanges(Request $request)
{
    $statusFilter = $request->query('status', 'all');

    $exchanges = Exchange::with(['provider', 'learner'])
        ->when($statusFilter !== 'all', fn ($q) => $q->where('status', $statusFilter))
        // Disputes first: they are the ones waiting for an admin.
        ->orderByRaw("case status when 'disputed' then 0 else 1 end")
        ->latest()
        ->paginate(15)
        ->withQueryString();

    $counts = Exchange::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

    return view('admin.exchanges', compact('exchanges', 'statusFilter', 'counts'));
}

public function showExchange(Exchange $exchange)
{
    $exchange->load(['provider', 'learner', 'disputedBy', 'schedules', 'ratings.rater', 'exchangeLessons.author', 'messages.sender']);

    return view('admin.exchanges-show', compact('exchange'));
}

public function updateExchangeStatus(Request $request, Exchange $exchange)
{
    $validated = $request->validate([
        'status' => 'required|in:ongoing,completed,pending,disputed,cancelled',
        'admin_notes' => 'nullable|string|max:2000',
    ]);

    $wasDisputed = $exchange->status === 'disputed';
    $changed = $exchange->status !== $validated['status'];

    if ($changed && $validated['status'] === 'disputed') {
        $validated['disputed_at'] = $exchange->disputed_at ?? now();
    }
    if ($changed && $wasDisputed) {
        $validated['resolved_at'] = now();
    }

    $exchange->update($validated);

    ActivityLog::record('exchange.updated', "Set exchange #{$exchange->id} to {$exchange->status}");

    // Both partners hear about it in the app's notifications (shown as an "admin" notice).
    if ($changed) {
        $message = $wasDisputed
            ? "An admin reviewed the dispute on your exchange ({$exchange->skillPair()}) and set it to {$exchange->status}."
                . ($exchange->admin_notes ? " Note from the admin: {$exchange->admin_notes}" : '')
            : "An admin changed your exchange ({$exchange->skillPair()}) to {$exchange->status}.";

        foreach (array_unique([$exchange->provider_id, $exchange->learner_id]) as $memberId) {
            Notification::notifyMember($memberId, $wasDisputed ? 'Dispute reviewed' : 'Exchange updated', $message, 'exchange', $exchange->id);
        }
    }

    return redirect()->route('admin.exchanges')->with('success', 'Exchange status updated.');
}

public function deleteExchange(Exchange $exchange)
{
    $id = $exchange->id;
    $pair = $exchange->skillPair();
    $members = array_unique([$exchange->provider_id, $exchange->learner_id]);

    // Its private lessons, chat pictures and rating proofs go with it, files included.
    $paths = StorageCleanup::forExchanges([$id]);
    $exchange->delete();
    StorageCleanup::delete($paths);

    ActivityLog::record('exchange.deleted', "Deleted exchange #{$id}");

    foreach ($members as $memberId) {
        Notification::notifyMember($memberId, 'Exchange removed', "An admin removed your exchange ({$pair}).", 'exchange', null);
    }

    return redirect()->route('admin.exchanges')->with('success', 'Exchange deleted.');
}

public function adminCategories(Request $request)
{
    $search = trim((string) $request->query('search', ''));

    $categories = Category::withCount(['skills', 'listings'])
        ->with(['skills' => fn ($q) => $q->orderBy('title')->select('id', 'category_id', 'title', 'icon', 'status')])
        ->when($search !== '', fn ($q) => $q->where(function ($w) use ($search) {
            $w->where('name', 'like', "%{$search}%")
              ->orWhereHas('skills', fn ($s) => $s->where('title', 'like', "%{$search}%"));
        }))
        ->orderBy('name')
        ->get();

    // Published catalog lessons per category, in one query.
    $lessonCounts = \Illuminate\Support\Facades\DB::table('lessons')
        ->join('skill_levels', 'skill_levels.id', '=', 'lessons.skill_level_id')
        ->join('skills', 'skills.id', '=', 'skill_levels.skill_id')
        ->where('lessons.status', 'published')
        ->selectRaw('skills.category_id as cid, COUNT(*) as total')
        ->groupBy('skills.category_id')
        ->pluck('total', 'cid');

    $uncategorized = Skill::whereNull('category_id')->count();

    return view('admin.categories', compact('categories', 'search', 'lessonCounts', 'uncategorized'));
}

public function storeCategory(Request $request)
{
    $name = $this->categoryName($request);

    $category = Category::create(['name' => $name]);
    ActivityLog::record('category.created', "Added category \"{$category->name}\"");

    return back()->with('success', 'Category added.');
}

public function deleteCategory(Category $category)
{
    $skills = $category->skills()->count();
    $name = $category->name;
    $category->delete();
    ActivityLog::record('category.deleted', "Deleted category \"{$name}\"".($skills > 0 ? " ({$skills} skills now uncategorized)" : ''));

    return back()->with('success', $skills > 0
        ? "Category deleted. Its {$skills} ".\Illuminate\Support\Str::plural('skill', $skills).' now have no category.'
        : 'Category deleted.');
}

public function createExchange()
{
    $users = User::where('role', 'user')->where('status', 'active')->orderBy('name')->get();

    return view('admin.exchanges-create', compact('users'));
}

public function storeExchange(Request $request)
{
    $validated = $request->validate([
        'provider_id' => 'required|exists:users,id',
        'learner_id' => 'required|exists:users,id|different:provider_id',
        // The provider teaches skill_offered. skill_wanted is what the learner teaches back;
        // leave it empty for a learner-only exchange (nothing is swapped back).
        'skill_offered' => 'required|string|max:255',
        'skill_wanted' => 'nullable|string|max:255',
        'status' => 'required|in:ongoing,completed,pending,disputed,cancelled',
    ]);

    $exchange = Exchange::create($validated);

    ActivityLog::record('exchange.created', "Created exchange {$exchange->skillPair()}");

    return redirect()->route('admin.exchanges')->with('success', 'Exchange created successfully.');
}

/** The trimmed category name, refusing a duplicate that only differs by capitals ("language" vs "Language"). */
private function categoryName(Request $request, ?int $ignoreId = null): string
{
    $request->merge(['name' => trim((string) $request->input('name'))]);
    $request->validate(['name' => ['required', 'string', 'max:255']]);

    $name = $request->input('name');
    $taken = Category::whereRaw('lower(name) = ?', [Str::lower($name)])
        ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
        ->exists();

    if ($taken) {
        throw \Illuminate\Validation\ValidationException::withMessages(['name' => 'A category with this name already exists.']);
    }

    return $name;
}

public function updateCategory(Request $request, Category $category)
{
    $name = $this->categoryName($request, $category->id);

    $old = $category->name;
    $category->update(['name' => $name]);
    ActivityLog::record('category.updated', "Renamed category \"{$old}\" to \"{$category->name}\"");

    return back()->with('success', 'Category updated.');
}

}
