<?php

namespace App\Http\Controllers\Talent;

use App\Http\Requests\Talent\ReorderRequest;
use App\Http\Requests\Talent\StoreCreditRequest;
use App\Http\Resources\ProjectCreditResource;
use App\Models\Project;
use App\Models\ProjectCredit;
use App\Services\ProjectCreditService;
use Illuminate\Http\JsonResponse;
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

    public function store(StoreCreditRequest $request, Project $project): JsonResponse
    {
        $this->ensureOwns($project);

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

    public function destroy(Project $project, ProjectCredit $credit): JsonResponse
    {
        $this->ensureOwns($project);
        abort_unless((int) $credit->project_id === (int) $project->getKey(), 404);

        $this->credits->remove($credit);

        return response()->success($this->payload($project), __('Credit removed.'));
    }

    public function reorder(ReorderRequest $request, Project $project): JsonResponse
    {
        $this->ensureOwns($project);
        $this->credits->reorder($project, $request->orderedIds());

        return response()->success(null, __('Order saved.'));
    }

    // ----- The credited person's side ----------------------------------------

    /**
     * "Credits" page — the confirmations they owe an answer to, plus the ones
     * already on their portfolio so withdrawing stays possible.
     */
    public function index(): View
    {
        return view('talent.credits', ['talent' => $this->talent()]);
    }

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
