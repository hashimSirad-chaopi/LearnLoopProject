<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Report;
use App\Models\Listing;
use App\Models\Exchange;
use Illuminate\Support\Facades\Hash;
use App\Models\Category;
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
    return view('admin.home', compact('totalUsers', 'totalListings', 'activeListings', 'activeSessions', 'pendingReports', 'activeTutors'));
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

    $listings = Listing::with('user')->when($search, function ($query, $search) {
        $query->where('title', 'like', "%{$search}%")
              ->orWhere('category', 'like', "%{$search}%");
    })->latest()->get();

    return view('admin.listing', compact('listings', 'search'));
}
    

    public function adminSettings()
    {
        return view('admin.settings');

    }
    public function deleteUser(User $user)
{
    $user->delete();

    return redirect()
        ->route('admin.users')
        ->with('success', 'User deleted successfully.');
}


public function adminReports(Request $request)
{
    $search = $request->query('search');
    $statusFilter = $request->query('status', 'all');

    $reports = Report::with(['reporter', 'reportedUser', 'listing'])
        ->when($search, function ($query, $search) {
            $query->where('title', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
        })
        ->when($statusFilter && $statusFilter !== 'all', function ($query) use ($statusFilter) {
            $query->where('status', $statusFilter);
        })
        ->latest()->get();

    return view('admin.reports', compact('reports', 'search', 'statusFilter'));
}

public function resolveReport(Report $report)
{
    $report->status = 'resolved';
    $report->save();

    return back()->with('success', 'Report resolved.');
}

public function dismissReport(Report $report)
{
    $report->status = 'dismissed';
    $report->save();

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

    $user->name = $validated['name'];
    $user->email = $validated['email'];
    $user->role = $validated['role'];

    if (!empty($validated['password'])) {
        $user->password = Hash::make($validated['password']);
    }

    $user->save();

    return redirect()->route('admin.users')->with('success', 'User updated successfully.');
}

public function createListing()
{
    $users = User::all();

    return view('admin.listings-create', compact('users'));
}

public function storeListing(Request $request)
{
    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
        'title' => 'required|string|max:255',
        'category' => 'required|string|max:255',
        'description' => 'required|string',
        'status' => 'required|in:active,pending,inactive',
    ]);

    Listing::create($validated);

    return redirect()->route('admin.listings')->with('success', 'Listing created successfully.');
}

public function editListing(Listing $listing)
{
    $users = User::all();

    return view('admin.listings-edit', compact('listing', 'users'));
}

public function updateListing(Request $request, Listing $listing)
{
    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
        'title' => 'required|string|max:255',
        'category' => 'required|string|max:255',
        'description' => 'required|string',
        'status' => 'required|in:active,pending,inactive',
    ]);

    $listing->update($validated);

    return redirect()->route('admin.listings')->with('success', 'Listing updated successfully.');
}

public function disableListing(Listing $listing)
{
    $listing->status = 'inactive';
    $listing->save();

    return back()->with('success', 'Listing disabled.');
}

public function deleteListing(Listing $listing)
{
    $listing->delete();

    return back()->with('success', 'Listing deleted.');
}

public function enableListing(Listing $listing)
{
    $listing->status = 'active';
    $listing->save();

    return back()->with('success', 'Listing enabled.');
}

public function deleteReport(Report $report)
{
    $report->delete();

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
        'reported_user_id' => 'required|exists:users,id',
        'listing_id' => 'nullable|exists:listings,id',
        'reason' => 'required|string',
        'status' => 'required|in:pending,resolved,dismissed',
    ]);

    Report::create($validated);

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

    $exchange->update($validated);

    return redirect()->route('admin.exchanges')->with('success', 'Exchange status updated.');
}

public function deleteExchange(Exchange $exchange)
{
    $exchange->delete();

    return redirect()->route('admin.exchanges')->with('success', 'Exchange deleted.');
}

public function adminCategories()
{
    $categories = Category::latest()->get();

    return view('admin.categories', compact('categories'));
}

public function storeCategory(Request $request)
{
    $request->validate(['name' => 'required|string|max:255|unique:categories,name']);

    Category::create($request->only('name'));

    return back()->with('success', 'Category added.');
}

public function deleteCategory(Category $category)
{
    $category->delete();

    return back()->with('success', 'Category deleted.');
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
        'learner_id' => 'required|exists:users,id',
        'skill_offered' => 'required|string|max:255',
        'skill_wanted' => 'required|string|max:255',
        'status' => 'required|in:ongoing,completed,pending,disputed',
    ]);

    Exchange::create($validated);

    return redirect()->route('admin.exchanges')->with('success', 'Exchange created successfully.');
}

public function updateCategory(Request $request, Category $category)
{
    $request->validate(['name' => 'required|string|max:255|unique:categories,name,' . $category->id]);

    $category->update($request->only('name'));

    return back()->with('success', 'Category updated.');
}

}
