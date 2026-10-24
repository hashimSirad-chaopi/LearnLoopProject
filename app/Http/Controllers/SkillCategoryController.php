<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Models\SkillCategory;
use Illuminate\Http\Request;

class SkillCategoryController extends Controller
{
    /**
     * List all categories with their skills.
     */
    public function index()
    {
        $categories = SkillCategory::with('skills')
            ->withCount('skills')
            ->orderBy('name')
            ->get();

        return view('admin.categories.index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Create a new top-level category (e.g. "Programming Language").
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:skill_categories,name',
        ]);

        SkillCategory::create($validated);

        return back()->with('success', 'Category added.');
    }

    /**
     * Rename a category.
     */
    public function update(Request $request, SkillCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:skill_categories,name,' . $category->id,
        ]);

        $category->update($validated);

        return back()->with('success', 'Category renamed.');
    }

    /**
     * Delete a category and all of its skills.
     */
    public function destroy(SkillCategory $category)
    {
        $category->delete();

        return back()->with('success', 'Category and its skills were deleted.');
    }

    /**
     * Add a new skill under a category (e.g. "Java" under "Programming Language").
     */
    public function storeSkill(Request $request, SkillCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:skills,name,NULL,id,skill_category_id,' . $category->id,
        ]);

        $category->skills()->create($validated);

        return back()->with('success', 'Skill added to ' . $category->name . '.');
    }

    /**
     * Rename a skill.
     */
    public function updateSkill(Request $request, Skill $skill)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:skills,name,' . $skill->id . ',id,skill_category_id,' . $skill->skill_category_id,
        ]);

        $skill->update($validated);

        return back()->with('success', 'Skill renamed.');
    }

    /**
     * Remove a skill from its category.
     */
    public function destroySkill(Skill $skill)
    {
        $skill->delete();

        return back()->with('success', 'Skill removed.');
    }
}
