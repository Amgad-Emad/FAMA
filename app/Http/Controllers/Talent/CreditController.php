<?php

namespace App\Http\Controllers\Talent;

use App\Http\Requests\Talent\ReorderRequest;
use App\Http\Requests\Talent\StoreCreditRequest;
use App\Http\Resources\ProjectCreditResource;
use App\Models\Project;
use App\Models\ProjectCredit;
use App\Models\Recognition;
use App\Services\ProjectCreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Collaborator credits (ADR-AA), from BOTH sides:
 *
 *  - the project OWNER adds, reorders and removes credits on their own project;
 *  - the CREDITED person accepts or declines the ones naming them.
 *
 * The two are separate authorisation questions and are checked separately:
 * owning the project never lets you answer on someone else's behalf, and being
 * credited never lets you edit someone else's credit block.
 */
class CreditController extends TalentController
{
    public function __construct(private readonly ProjectCreditService $credits) {}

    // ----- The project owner's side ------------------------------------------

    /**
     * Credit someone on the owner's project. On-platform credits land pending
     * and notify the person (post-commit); off-platform names list at once.
     */
    public function store(StoreCreditRequest $request, Project $project): JsonResponse
    {
        $this->authorizeTalent('update', $project);

        $credit = $this->credits->add($project, $request->validated());

        // POST-COMMIT: a notification must never ride inside the write.
        $this->credits->notifyRequested($credit);

        return response()->success(
            $this->payload($project),
            $credit->isOnPlatform()
                ? __('Credit sent — it appears once they confirm it.')
                : __('Credit added.'),
            status: 201,
        );
    }

    /**
     * Remove a credit from the owner's project.
     */
    public function destroy(Project $project, ProjectCredit $credit): JsonResponse
    {
        $this->authorizeTalent('update', $project);
        abort_unless((int) $credit->project_id === (int) $project->getKey(), 404);

        $this->credits->remove($credit);

        return response()->success($this->payload($project), __('Credit removed.'));
    }

    /**
     * Persist the order of a project's credit block.
     */
    public function reorder(ReorderRequest $request, Project $project): JsonResponse
    {
        $this->authorizeTalent('update', $project);
        $this->credits->reorder($project, $request->orderedIds());

        return response()->success(null, __('Order saved.'));
    }

    // ----- The credited person's side ----------------------------------------

    /**
     * "Credits & recognitions" page (fama-ui `credits.html`) — the confirmations
     * they owe an answer to, the credited work already on their portfolio (so
     * leaving stays possible), and the peer recognitions they have received.
     *
     * Recognitions are READ-ONLY here (moderation is admin-side): approved and
     * still-pending ones, newest first, with the author eager-loaded. The
     * "Show on profile" switch drives the talent's own profile-level
     * `recognitions` block through the existing block-visibility endpoint.
     */
    public function index(): View
    {
        $talent = $this->talent();

        $recognitions = $talent->recognitions()
            ->whereIn('status', ['pending', 'approved'])
            ->with('author.media')
            ->latest('id')
            ->get();

        $block = $talent->profileBlocks()
            ->whereNull('talent_type_id')
            ->whereHas('blockType', fn ($q) => $q->where('key', 'recognitions'))
            ->first();

        return view('talent.credits', [
            'talent' => $talent,
            // Which credited skills already have a tab on this profile.
            'heldSkillIds' => $talent->talentTypes()->pluck('talent_types.id')->map('intval')->values()->all(),
            'recognitions' => $recognitions,
            'recognitionsBlock' => $block,
        ]);
    }

    /**
     * The credits naming the signed-in talent: pending first, then accepted.
     */
    public function data(): JsonResponse
    {
        $credits = $this->talent()->projectCredits()
            ->whereIn('status', ['pending', 'accepted'])
            ->with(['project.talent', 'project.media', 'talentType'])
            ->orderByRaw("FIELD(status, 'pending', 'accepted')")
            ->latest('id')
            ->get();

        return response()->success([
            'credits' => ProjectCreditResource::collection($credits),
        ]);
    }

    /**
     * Confirm or refuse a credit naming YOU. Ownership here is the credit's
     * `talent_id` — not the project's owner, who must never be able to answer
     * on the collaborator's behalf.
     */
    public function respond(ProjectCredit $credit, string $action): JsonResponse
    {
        abort_unless((int) $credit->talent_id === (int) $this->talent()->getKey(), 403);

        match ($action) {
            'accept' => $this->credits->accept($credit),
            'decline' => $this->credits->decline($credit),
            default => abort(404),
        };

        return response()->success(
            null,
            $action === 'accept' ? __('Credit confirmed.') : __('Credit declined.'),
        );
    }

    /**
     * Hide a confirmed credit naming YOU from your profile, or show it again
     * (ADR-AI). It stays on the project page — only your portfolio changes.
     */
    public function hideCredit(Request $request, ProjectCredit $credit): JsonResponse
    {
        abort_unless((int) $credit->talent_id === (int) $this->talent()->getKey(), 403);
        abort_unless((string) $credit->status === 'accepted', 422, __('Only a confirmed credit can be hidden.'));
        $hidden = $request->validate(['hidden' => ['required', 'boolean']])['hidden'];

        $credit->update(['hidden_at' => $hidden ? now() : null]);

        return response()->success(['hidden' => $hidden], $hidden ? __('Hidden from your profile.') : __('Back on your profile.'));
    }

    /**
     * Hide a recognition written for YOU from your profile, or show it again
     * (ADR-AI). Moderation's decision is untouched; this is the owner's choice.
     */
    public function hideRecognition(Request $request, Recognition $recognition): JsonResponse
    {
        abort_unless((int) $recognition->talent_id === (int) $this->talent()->getKey(), 403);
        $hidden = $request->validate(['hidden' => ['required', 'boolean']])['hidden'];

        $recognition->update(['hidden_at' => $hidden ? now() : null]);

        return response()->success(['hidden' => $hidden], $hidden ? __('Hidden from your profile.') : __('Back on your profile.'));
    }

    /**
     * A project's credits, for the owner's editor.
     *
     * @return array<string, mixed>
     */
    private function payload(Project $project): array
    {
        return [
            'credits' => ProjectCreditResource::collection(
                $project->load(['credits.talent', 'credits.talentType'])->credits
            ),
        ];
    }
}
