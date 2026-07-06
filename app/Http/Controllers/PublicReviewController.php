<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Talent;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Public review submission (talent-spec) — a past client leaves a review, which
 * lands as pending (is_approved = false) for the talent to moderate. Public form;
 * the submit is Ajax (JSON envelope) so the page never reloads.
 */
class PublicReviewController extends Controller
{
    public function create(string $slug): View
    {
        $talent = $this->publishedTalent($slug);

        return view('public.review', ['talent' => $talent]);
    }

    public function store(StoreReviewRequest $request, string $slug): JsonResponse
    {
        $talent = $this->publishedTalent($slug);

        $talent->reviews()->create($request->validated() + [
            'is_approved' => false,
            'status' => 'pending',
            'reviewed_at' => now(),
        ]);

        return response()->success(null, __('Thank you — your review has been submitted for approval.'), status: 201);
    }

    private function publishedTalent(string $slug): Talent
    {
        return Talent::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();
    }
}
