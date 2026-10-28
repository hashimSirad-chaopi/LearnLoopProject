<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\ReportAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    /** "My Reports": what this member filed and where each stands (pending | resolved | dismissed). */
    public function index(Request $request)
    {
        $reports = Report::with(['reportedUser', 'listing', 'attachments'])
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
            // Proof: up to 3 photos and 1 video (50 MB), sent as multipart images[] and videos[].
            'images' => 'sometimes|array|max:'.ReportAttachment::MAX_IMAGES,
            'images.*' => 'image|mimes:jpg,jpeg,png,webp,gif|max:'.ReportAttachment::MAX_IMAGE_KB,
            'videos' => 'sometimes|array|max:'.ReportAttachment::MAX_VIDEOS,
            'videos.*' => 'file|mimetypes:video/mp4,video/quicktime,video/webm,video/3gpp,video/x-matroska,video/x-msvideo|max:'.ReportAttachment::MAX_VIDEO_KB,
        ], [
            'images.max' => 'You can add up to '.ReportAttachment::MAX_IMAGES.' photos.',
            'videos.max' => 'You can add up to '.ReportAttachment::MAX_VIDEOS.' video.',
            'videos.*.max' => 'The video must be 50 MB or smaller.',
            'videos.*.mimetypes' => 'The video must be MP4, MOV, WebM, 3GP, MKV or AVI.',
            'videos.*.uploaded' => 'The video could not be uploaded. It may be larger than the server allows (50 MB).',
            'images.*.max' => 'Each photo must be 10 MB or smaller.',
            'images.*.uploaded' => 'A photo could not be uploaded.',
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

        foreach ((array) $request->file('images', []) as $file) {
            $this->saveAttachment($report, $file, 'image');
        }
        foreach ((array) $request->file('videos', []) as $file) {
            $this->saveAttachment($report, $file, 'video');
        }

        \App\Support\AdminMail::newReport($report);

        return response()->json(['report' => $report->load('reportedUser', 'listing', 'attachments')], 201);
    }

    private function saveAttachment(Report $report, $file, string $type): void
    {
        $report->attachments()->create([
            'type' => $type,
            'path' => $file->store('report-proofs', 'public'),
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ]);
    }
}
