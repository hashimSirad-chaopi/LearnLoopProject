<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public function adminHome()
    {
        return view('admin.home');
    }
}