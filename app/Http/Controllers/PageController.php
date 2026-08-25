<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public function adminHome()
    {
        return view('admin.home');
    }

    public function userHome()
    {
        return view('user.home');
    }

    public function matches()
    {
        return view('user.matches');
    }

    public function exchanges()
    {
        return view('user.exchanges');
    }

    public function messages()
    {
        return view('user.messages');
    }

    public function profile()
    {
        return view('user.profile');
    }
}