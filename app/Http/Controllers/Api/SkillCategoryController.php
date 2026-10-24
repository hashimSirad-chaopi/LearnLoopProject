<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;

class SkillCategoryController extends Controller
{
    /** Populates the category dropdown on the Create Post screen. */
    public function index()
    {
        return response()->json(['categories' => Category::orderBy('name')->get(['id', 'name'])]);
    }
}
