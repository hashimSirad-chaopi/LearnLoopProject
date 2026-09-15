<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Report;
use App\Models\Listing;
use App\Models\Exchange;
class PageController extends Controller
{
    public function adminHome()
{
    $totalUsers = User::count();
    $totalListings = Listing::count();
    $activeListings = Listing::where('status', 'active')->count();
    $activeSessions = Exchange::where('status', 'ongoing')->count();
    $pendingReports = Report::where('status', 'pending')->count();

    return view('admin.home', compact('totalUsers', 'totalListings', 'activeListings', 'activeSessions', 'pendingReports'));
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

}
