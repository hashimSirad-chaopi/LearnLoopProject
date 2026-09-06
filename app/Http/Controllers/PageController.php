<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;


class PageController extends Controller
{
    public function adminHome()
    {
        return view('admin.home');
    }

   public function adminUsers()
{
    $users = User::all();

    return view('admin.users', compact('users'));
}

    public function adminListings()
    {
        return view('admin.listing');
    }

    public function adminReports()
    {
        return view('admin.reports');
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
}
