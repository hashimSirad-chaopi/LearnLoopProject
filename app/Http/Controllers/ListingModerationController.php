<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Support\ListingModeration;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Admin > Listings: disable, delete (both with a reason) and restore. No approval step. */
class ListingModerationController extends Controller
{
    private function reasonRules(Request $request): array
    {
        return [
            'reason' => 'required|in:'.implode(',', ListingModeration::reasonKeys()),
            // "Other" has to be explained in words.
            'note' => [Rule::requiredIf($request->input('reason') === 'other'), 'nullable', 'string', 'max:500'],
        ];
    }

    /** The Listings page: every listing, newest first, with the filters. */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = in_array($request->query('status'), ['active', 'disabled', 'closed'], true) ? $request->query('status') : 'all';
        $sort = in_array($request->query('sort'), ['oldest', 'title', 'status'], true) ? $request->query('sort') : 'newest';

        $listings = Listing::with(['user', 'category', 'offersSkill', 'wantsSkill'])
            ->when($request->query('user'), fn ($q, $id) => $q->where('user_id', (int) $id))
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhereHas('offersSkill', fn ($s) => $s->where('title', 'like', "%{$search}%"))
                        ->orWhereHas('wantsSkill', fn ($s) => $s->where('title', 'like', "%{$search}%"))
                        ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($status === 'active', fn ($q) => $q->where('status', 'active'))
            ->when($status === 'disabled', fn ($q) => $q->where('status', 'inactive')->whereNotNull('moderated_at'))
            ->when($status === 'closed', fn ($q) => $q->where('status', 'inactive')->whereNull('moderated_at'))
            ->when(true, function ($q) use ($sort) {
                match ($sort) {
                    'oldest' => $q->oldest(),
                    'title' => $q->orderBy('title'),
                    'status' => $q->orderByRaw("CASE WHEN status = 'inactive' AND moderated_at IS NOT NULL THEN 0 WHEN status = 'active' THEN 1 ELSE 2 END")->latest(),
                    default => $q->latest(),
                };
            })
            ->paginate(15)
            ->withQueryString();

        return view('admin.listing', compact('listings', 'search', 'status', 'sort'));
    }

    public function disable(Request $request, Listing $listing)
    {
        $data = $request->validate($this->reasonRules($request));

        ListingModeration::disable($listing, $data['reason'], $data['note'] ?? null);

        return back()->with('success', 'Listing disabled. The user was told why.');
    }

    public function destroy(Request $request, Listing $listing)
    {
        $data = $request->validate($this->reasonRules($request));

        ListingModeration::delete($listing, $data['reason'], $data['note'] ?? null);

        return back()->with('success', 'Listing deleted. The user was told why.');
    }

    public function restore(Listing $listing)
    {
        ListingModeration::restore($listing);

        return back()->with('success', 'Listing restored to the feed.');
    }

    /** Disable or delete (with one reason) or restore several listings at once. */
    public function bulk(Request $request)
    {
        $action = $request->input('bulk');
        $rules = [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:listings,id',
            'bulk' => 'required|in:disable,delete,restore',
        ];
        if ($action !== 'restore') {
            $rules += $this->reasonRules($request);
        }
        $data = $request->validate($rules);

        $listings = Listing::whereIn('id', $data['ids'])->get();
        $done = 0;

        foreach ($listings as $listing) {
            if ($action === 'disable') {
                if ($listing->isDisabledByAdmin()) {
                    continue;
                }
                ListingModeration::disable($listing, $data['reason'], $data['note'] ?? null, true);
            } elseif ($action === 'delete') {
                ListingModeration::delete($listing, $data['reason'], $data['note'] ?? null, true);
            } else {
                if (! $listing->isDisabledByAdmin()) {
                    continue;
                }
                ListingModeration::restore($listing, true);
            }
            $done++;
        }

        $verb = ['disable' => 'disabled', 'delete' => 'deleted', 'restore' => 'restored'][$action];

        return back()->with('success', $done.' '.Str::plural('listing', $done)." {$verb}.");
    }
}
