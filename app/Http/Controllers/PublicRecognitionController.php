<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecognitionRequest;
use App\Models\Recognition;
use App\Models\Talent;
use App\Services\RecognitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Peer recognition (ADR-Z) — one talent vouches for another.
 *
 * The sibling of PublicReviewController, and the difference is the author. A
 * review is written by a BRAND after a booking completes and needs no account;
 * a recognition is written by a TALENT, needs no booking, and therefore MUST be
 * attributable — so this route requires the `talent` guard. That is what stops
 * the section becoming anonymous praise, and it is why recognitions work from
 * day one while reviews cannot.
 *
 * Submissions land pending for the admin moderation queue, exactly like reviews.
 */
class PublicRecognitionController extends Controller
{
    public function __construct(private readonly RecognitionService $recognitions) {}

    /**
     * The write-a-recognition form. A guest is sent to the talent login with the
     * form stored as the intended URL, so signing in lands them back here rather
     * than on a dashboard.
     */
    public function create(string $slug): View|RedirectResponse
    {
        $subject = $this->publishedTalent($slug);
        $author = auth('talent')->user();

        if ($author === null) {
            return redirect()->guest(route('login', ['role' => 'talent']));
        }

        if ($subject->is($author)) {
            return redirect(url($subject->slug))
                ->with('status', __('You cannot write a recognition for yourself.'));
        }

        return view('public.recognition', [
            'talent' => $subject,
            // A peer vouches once; re-opening the form edits what they said.
            'existing' => Recognition::query()
                ->where('talent_id', $subject->getKey())
                ->where('author_talent_id', $author->getKey())
                ->first(),
        ]);
    }

    public function store(StoreRecognitionRequest $request, string $slug): JsonResponse
    {
        $subject = $this->publishedTalent($slug);
        $author = auth('talent')->user();

        abort_if($author === null, 401);

        $this->recognitions->submit($subject, $author, $request->validated());

        return response()->success(
            null,
            __('Thank you — your recognition has been submitted for approval.'),
            status: 201,
        );
    }

    private function publishedTalent(string $slug): Talent
    {
        return Talent::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();
    }
}
