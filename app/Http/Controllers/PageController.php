<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Report;
use App\Models\Listing;
use App\Models\Exchange;
use Illuminate\Support\Facades\Hash;
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

   public function adminUsers()
{
    $users = User::all();

    return view('admin.users', compact('users'));
}

    public function adminListings()
{
    $listings = Listing::with('user')->latest()->get();

    return view('admin.listing', compact('listings'));
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


public function adminReports()
{
    $reports = Report::with(['reporter', 'reportedUser', 'listing'])->latest()->get();

    return view('admin.reports', compact('reports'));
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
    ]);

    $user->update($validated);

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

}
