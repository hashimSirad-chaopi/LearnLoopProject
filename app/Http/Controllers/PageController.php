<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public function adminHome()
    {
        return view('admin.home');
    }

    public function adminUsers()
    {
        return view('admin.users');
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
}
