<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Report;
use App\Models\Listing;
use App\Models\Exchange;
use Illuminate\Support\Facades\Hash;
use App\Models\Category;
use App\Models\ActivityLog;
use Illuminate\Validation\Rule;
use App\Models\Subcategory;
class PageController extends Controller
{
    public function adminHome()
{
    $totalUsers = User::count();
    $totalListings = Listing::count();
    $activeListings = Listing::where('status', 'active')->count();
    $activeSessions = Exchange::where('status', 'ongoing')->count();
    $pendingReports = Report::where('status', 'pending')->count();
    $activeTutors = Listing::where('status', 'active')->distinct('user_id')->count('user_id');

    $activities = ActivityLog::latest()->take(8)->get()->map(fn ($log) => [
    'text' => $log->description,
    'time' => $log->created_at,
]);

    return view('admin.home', compact(
        'totalUsers', 'totalListings', 'activeListings',
        'activeSessions', 'pendingReports', 'activeTutors', 'activities'
    ));
}

   public function adminUsers(Request $request)
{
    $search = $request->query('search');

    $users = User::when($search, function ($query, $search) {
        $query->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
    })->get();

    return view('admin.users', compact('users', 'search'));
}

  
public function adminListings(Request $request)
{
    $search = $request->query('search');

    $listings = Listing::with(['user', 'category', 'subcategory'])
        ->when($search, function ($query, $search) {
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('title', 'like', "%{$search}%")
                    ->orWhereHas('category', function ($categoryQuery) use ($search) {
                        $categoryQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('subcategory', function ($subcategoryQuery) use ($search) {
                        $subcategoryQuery->where('name', 'like', "%{$search}%");
                    });
            });
        })
        ->latest()
        ->get();

    return view('admin.listing', compact('listings', 'search'));
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

    $user->delete();

    ActivityLog::record('user.deleted', "Deleted user {$user->name} ({$user->email})");


    return redirect()->route('admin.users')->with('success', 'User deleted successfully.');
}

public function suspendUser(User $user)
{
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

    $user->status = 'suspended';
    $user->save();

    ActivityLog::record('user.suspended', "Suspended user {$user->name}");

    return back()->with('success', 'User suspended successfully.');
}

public function activateUser(User $user)
{
    $user->status = 'active';
    $user->save();

    ActivityLog::record('user.activated', "Reactivated user {$user->name}");

    return back()->with('success', 'User reactivated successfully.');
}

public function adminActivity()
{
    $logs = ActivityLog::with('admin')->latest()->paginate(15);

    return view('admin.activity', compact('logs'));
}
public function adminReports(Request $request)
{
    $search = $request->query('search');
    $statusFilter = $request->query('status', 'all');

    $reports = Report::with(['reporter', 'reportedUser', 'listing'])
        ->when($search, function ($query, $search) {
    $query->where(function ($searchQuery) use ($search) {
        $searchQuery->where('title', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%");
          });
        })
        ->when($statusFilter && $statusFilter !== 'all', function ($query) use ($statusFilter) {
            $query->where('status', $statusFilter);
        })
        ->latest()->get();

    return view('admin.reports', compact('reports', 'search', 'statusFilter'));
}


public function resolveReport(Report $report)
{
    if ($report->status !== 'pending') {
        return back()->withErrors([
            'report' => 'Only pending reports can be resolved.',
        ]);
    }

    $report->status = 'resolved';
    $report->save();

    ActivityLog::record(
        'report.resolved',
        "Resolved report \"{$report->title}\""
    );

    return back()->with('success', 'Report resolved.');
}


public function dismissReport(Report $report)
{
    if ($report->status !== 'pending') {
        return back()->withErrors([
            'report' => 'Only pending reports can be dismissed.',
        ]);
    }

    $report->status = 'dismissed';
    $report->save();

    ActivityLog::record(
        'report.dismissed',
        "Dismissed report \"{$report->title}\""
    );

    return back()->with('success', 'Report dismissed.');
}

public function createUser()
{
    return view('admin.users-create');
}

public function storeUser(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:6',
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
    return view('admin.users-show', compact('user'));
}

public function editUser(User $user)
{
    return view('admin.users-edit', compact('user'));
}

public function updateUser(Request $request, User $user)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,' . $user->id,
        'role' => 'required|in:admin,user',
        'password' => 'nullable|min:6',
    ]);

            
        if (
            (int) $user->id === (int) auth()->id()
            && $validated['role'] !== $user->role
        ) {
            return back()->withErrors([
                'role' => "You cannot change your own role.",
            ]);
        }

    $wasAdmin = $user->role === 'admin';

    if ($wasAdmin && $user->status === 'active' && $validated['role'] !== 'admin' && User::where('role', 'admin')->where('status', 'active')->count() <= 1) {
    return back()->withErrors(['role' => 'Cannot demote the last active admin.']);
    }

    $user->name = $validated['name'];
    $user->email = $validated['email'];
    $user->role = $validated['role'];

    if (!empty($validated['password'])) {
        $user->password = Hash::make($validated['password']);
    }

    $user->save();

    ActivityLog::record('user.updated', "Updated user {$user->name}");

    return redirect()->route('admin.users')->with('success', 'User updated successfully.');
}

public function createListing()
{
    $users = User::all();

    $categories = Category::with('subcategories')
        ->orderBy('name')
        ->get();

    return view('admin.listings-create', compact('users', 'categories'));
}


public function storeListing(Request $request)
{
    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
        'title' => 'required|string|max:255',
        'category_id' => 'required|exists:categories,id',

        'subcategory_id' => [
            'required',
            'integer',
            Rule::exists('subcategories', 'id')
                ->where(fn ($query) => $query->where(
                    'category_id',
                    $request->input('category_id')
                )),
        ],

        'level' => 'required|in:beginner,intermediate,advanced',
        'description' => 'required|string',
        'status' => 'required|in:active,pending,inactive',
    ]);

    Listing::create($validated);

    ActivityLog::record(
        'listing.created',
        "Created listing \"{$validated['title']}\""
    );

    return redirect()
        ->route('admin.listings')
        ->with('success', 'Listing created successfully.');
}

public function editListing(Listing $listing)
{
    $users = User::all();

    $categories = Category::with('subcategories')
        ->orderBy('name')
        ->get();

    return view('admin.listings-edit', compact(
        'listing',
        'users',
        'categories'
    ));
}


public function updateListing(Request $request, Listing $listing)
{
    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
        'title' => 'required|string|max:255',
        'category_id' => 'required|exists:categories,id',

        'subcategory_id' => [
            'required',
            'integer',
            Rule::exists('subcategories', 'id')
                ->where(fn ($query) => $query->where(
                    'category_id',
                    $request->input('category_id')
                )),
        ],

        'level' => 'required|in:beginner,intermediate,advanced',
        'description' => 'required|string',
        'status' => 'required|in:active,pending,inactive',
    ]);

    $listing->update($validated);

    ActivityLog::record(
        'listing.updated',
        "Updated listing \"{$listing->title}\""
    );

    return redirect()
        ->route('admin.listings')
        ->with('success', 'Listing updated successfully.');
}


public function approveListing(Listing $listing)
{
    if ($listing->status !== 'pending') {
        return back()->withErrors([
            'listing' => 'Only pending listings can be approved.',
        ]);
    }

    $listing->status = 'active';
    $listing->save();

    ActivityLog::record(
        'listing.approved',
        "Approved listing \"{$listing->title}\" (pending to active)"
    );

    return back()->with('success', 'Listing approved and activated.');
}

public function rejectListing(Listing $listing)
{
    if ($listing->status !== 'pending') {
        return back()->withErrors([
            'listing' => 'Only pending listings can be rejected.',
        ]);
    }

    $listing->status = 'inactive';
    $listing->save();

    ActivityLog::record(
        'listing.rejected',
        "Rejected listing \"{$listing->title}\" (pending to inactive)"
    );

    return back()->with('success', 'Listing rejected.');
}

public function disableListing(Listing $listing)
{
    if ($listing->status !== 'active') {
        return back()->withErrors([
            'listing' => 'Only active listings can be disabled.',
        ]);
    }

    $listing->status = 'inactive';
    $listing->save();

    ActivityLog::record(
        'listing.disabled',
        "Disabled listing \"{$listing->title}\" (active to inactive)"
    );

    return back()->with('success', 'Listing disabled.');
}

public function enableListing(Listing $listing)
{
    if ($listing->status !== 'inactive') {
        return back()->withErrors([
            'listing' => 'Only inactive listings can be enabled.',
        ]);
    }

    $listing->status = 'active';
    $listing->save();

    ActivityLog::record(
        'listing.enabled',
        "Enabled listing \"{$listing->title}\" (inactive to active)"
    );

    return back()->with('success', 'Listing enabled.');
}
public function deleteListing(Listing $listing)
{
    $listing->delete();

    ActivityLog::record('listing.deleted', "Deleted listing \"{$listing->title}\"");

    return back()->with('success', 'Listing deleted.');
}

public function deleteReport(Report $report)
{
    $report->delete();

    ActivityLog::record('report.deleted', "Deleted report \"{$report->title}\"");

    return back()->with('success', 'Report deleted.');
}

public function createReport()
{
    $users = User::all();
    $listings = Listing::all();

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

    Report::create($validated);

    ActivityLog::record('report.created', "Filed report \"{$validated['title']}\"");

    return redirect()->route('admin.reports')->with('success', 'Report created successfully.');
}

public function adminExchanges()
{
    $exchanges = Exchange::with(['provider', 'learner'])->latest()->get();

    return view('admin.exchanges', compact('exchanges'));
}

public function showExchange(Exchange $exchange)
{
    return view('admin.exchanges-show', compact('exchange'));
}


public function updateExchangeStatus(Request $request, Exchange $exchange)
{
    $validated = $request->validate([
        'status' => 'required|in:ongoing,completed,pending,disputed',
    ]);

    $currentStatus = $exchange->status;
    $newStatus = $validated['status'];

    // Submitting the existing status is harmless.
    if ($currentStatus === $newStatus) {
        return redirect()
            ->route('admin.exchanges')
            ->with('success', 'Exchange status is unchanged.');
    }

    // Define the permitted status transitions.
    $allowedTransitions = [
        'pending' => ['ongoing', 'disputed'],
        'ongoing' => ['completed', 'disputed'],
        'disputed' => ['ongoing', 'completed'],
        'completed' => [],
    ];

    if (! in_array(
        $newStatus,
        $allowedTransitions[$currentStatus] ?? [],
        true
    )) {
        return back()
            ->withErrors([
                'status' => "Cannot change an exchange from {$currentStatus} to {$newStatus}.",
            ])
            ->withInput();
    }

    $exchange->status = $newStatus;
    $exchange->save();

    ActivityLog::record(
        'exchange.updated',
        "Changed exchange #{$exchange->id} from {$currentStatus} to {$newStatus}"
    );

    return redirect()
        ->route('admin.exchanges')
        ->with('success', 'Exchange status updated.');
}

public function deleteExchange(Exchange $exchange)
{
    $exchange->delete();

    ActivityLog::record('exchange.deleted', "Deleted exchange #{$exchange->id}");

    return redirect()->route('admin.exchanges')->with('success', 'Exchange deleted.');
}

public function adminCategories()
{
    $categories = Category::latest()->get();

    return view('admin.categories', compact('categories'));
}

public function storeCategory(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255|unique:categories,name',
        'description' => 'required|string|max:2000',
    ]);

    $category = Category::create($validated);

    ActivityLog::record(
        'category.created',
        "Created category \"{$category->name}\""
    );

    return back()->with('success', 'Category added.');
}


public function deleteCategory(Category $category)
{
    if ($category->listings()->exists()) {
        return back()->withErrors([
            'category' => 'This category cannot be deleted because listings are using it. Reassign those listings to another category first.',
        ]);
    }

    $categoryName = $category->name;

    $category->delete();

    ActivityLog::record(
        'category.deleted',
        "Deleted category {$categoryName}"
    );

    return back()->with('success', 'Category deleted.');
}


public function adminSubcategories()
{
    $categories = Category::orderBy('name')->get();

    $subcategories = Subcategory::with('category')
        ->withCount('listings')
        ->orderBy('category_id')
        ->orderBy('name')
        ->get();

    return view('admin.subcategories', compact(
        'categories',
        'subcategories'
    ));
}

public function storeSubcategory(Request $request)
{
    $validated = $request->validate([
        'category_id' => 'required|integer|exists:categories,id',
        'name' => [
            'required',
            'string',
            'max:150',
            Rule::unique('subcategories', 'name')
                ->where(fn ($query) => $query->where(
                    'category_id',
                    $request->input('category_id')
                )),
        ],
        'description' => 'required|string|max:2000',
    ]);

    $subcategory = Subcategory::create($validated);

    ActivityLog::record(
        'subcategory.created',
        "Created subcategory \"{$subcategory->name}\" under \"{$subcategory->category->name}\""
    );

    return redirect()
        ->route('admin.subcategories')
        ->with('success', 'Subcategory added successfully.');
}

public function updateSubcategory(Request $request, Subcategory $subcategory)
{
    $validated = $request->validate([
        'category_id' => 'required|integer|exists:categories,id',
        'name' => [
            'required',
            'string',
            'max:150',
            Rule::unique('subcategories', 'name')
                ->where(fn ($query) => $query->where(
                    'category_id',
                    $request->input('category_id')
                ))
                ->ignore($subcategory->id),
        ],
        'description' => 'required|string|max:2000',
    ]);

    // Moving a skill used by listings could create mismatched relationships.
    if (
        (int) $validated['category_id'] !== (int) $subcategory->category_id
        && $subcategory->listings()->exists()
    ) {
        return back()->withErrors([
            'category_id' => 'This skill cannot be moved while listings use it. Reassign those listings first.',
        ])->withInput();
    }

    $previousName = $subcategory->name;
   

    $subcategory->update($validated);

    $newCategory = Category::findOrFail($validated['category_id']);

    ActivityLog::record(
        'subcategory.updated',
        "Updated subcategory \"{$previousName}\" to \"{$subcategory->name}\" under \"{$newCategory->name}\""
    );

    return redirect()
        ->route('admin.subcategories')
        ->with('success', 'Subcategory updated successfully.');
}

public function deleteSubcategory(Subcategory $subcategory)
{
    if ($subcategory->listings()->exists()) {
        return back()->withErrors([
            'subcategory' => 'This skill cannot be deleted because listings are using it. Reassign those listings first.',
        ]);
    }

    $name = $subcategory->name;
    $categoryName = $subcategory->category->name;

    $subcategory->delete();

    ActivityLog::record(
        'subcategory.deleted',
        "Deleted subcategory \"{$name}\" from \"{$categoryName}\""
    );

    return redirect()
        ->route('admin.subcategories')
        ->with('success', 'Subcategory deleted successfully.');
}


public function createExchange()
{
    $users = User::all();

    return view('admin.exchanges-create', compact('users'));
}

public function storeExchange(Request $request)
{
    $validated = $request->validate([
        'provider_id' => 'required|exists:users,id',
        'learner_id' => 'required|exists:users,id|different:provider_id',
        'skill_offered' => 'required|string|max:255',
        'skill_wanted' => 'required|string|max:255',
        'status' => 'required|in:ongoing,completed,pending,disputed',
    ]);

    Exchange::create($validated);

    ActivityLog::record('exchange.created', "Created exchange {$validated['skill_offered']} ↔ {$validated['skill_wanted']}");

    return redirect()->route('admin.exchanges')->with('success', 'Exchange created successfully.');
}

public function updateCategory(Request $request, Category $category)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
        'description' => 'required|string|max:2000',
    ]);

    $previousName = $category->name;
    $previousDescription = $category->description;

    $category->update($validated);

    if (
        $previousName !== $category->name ||
        $previousDescription !== $category->description
    ) {
        ActivityLog::record(
            'category.updated',
            "Updated category \"{$previousName}\" to \"{$category->name}\""
        );
    }

    return back()->with('success', 'Category updated.');
}

}
