<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    /** "My Reports": what this member filed and where each stands (pending | resolved | dismissed). */
    public function index(Request $request)
    {
        $reports = Report::with(['reportedUser', 'listing'])
            ->where('reported_by', $request->user()->id)
            ->latest()
            ->get();

        return response()->json(['reports' => $reports]);
    }

    /** Report screen. Same rules as the admin form: you cannot report yourself. */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'reported_user_id' => ['required', 'exists:users,id', Rule::notIn([$request->user()->id])],
            'listing_id' => 'nullable|exists:listings,id',
            'reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $report = Report::create([
            'title' => $request->title,
            'reported_by' => $request->user()->id,
            'reported_user_id' => $request->reported_user_id,
            'listing_id' => $request->listing_id,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        \App\Support\AdminMail::newReport($report);

        return response()->json(['report' => $report->load('reportedUser', 'listing')], 201);
    }
}
