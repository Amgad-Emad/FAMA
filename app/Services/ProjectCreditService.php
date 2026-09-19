<?php

namespace App\Services;

use App\Models\BlockType;
use App\Models\Project;
use App\Models\ProjectCredit;
use App\Models\Talent;
use App\Models\TalentType;
use App\Notifications\Credits\ProjectCreditAccepted;
use App\Notifications\Credits\ProjectCreditDeclined;
use App\Notifications\Credits\ProjectCreditRequested;
use App\States\ProjectCredit\Accepted;
use App\States\ProjectCredit\Declined;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Collaborator credits — the ONLY write path (ADR-AA).
 *
 * THE rule this service exists to enforce: tagging someone writes a credit onto
 * THEIR portfolio, so an on-platform credit needs THEIR confirmation. Without
 * it, anyone could attach a name to work that person did not do. Everything
 * else here follows from that one sentence.
 */
class ProjectCreditService extends Service
{
    /** A call sheet longer than this is a cast list, not a credit block. */
    private const MAX = 20;

    /**
     * Credit someone on a project.
     *
     * An on-platform credit (`talent_id`) lands PENDING and notifies them. An
     * off-platform one (`name`) is LISTED immediately: there is nobody to ask,
     * and it never leaves this project, so it cannot be used to claim credit on
     * anyone's behalf.
     *
     * @param  array<string, mixed>  $data
     */
    public function add(Project $project, array $data): ProjectCredit
    {
        [$collaborator, $type] = $this->assertShape($project, $data);

        return $this->runInTransaction(function () use ($project, $collaborator, $type, $data): ProjectCredit {
            if ($project->credits()->count() >= self::MAX) {
                throw new InvalidArgumentException(
                    __('You can credit up to :max people on one project.', ['max' => self::MAX])
                );
            }

            if ($collaborator !== null && $project->credits()->where('talent_id', $collaborator->getKey())->exists()) {
                throw new InvalidArgumentException(__('They are already credited on this project.'));
            }

            $credit = $project->credits()->create([
                'talent_id' => $collaborator?->getKey(),
                'name' => $collaborator === null ? $data['name'] : null,
                'talent_type_id' => $type->getKey(),
                'role' => $data['role'],
                'status' => $collaborator === null ? 'listed' : 'pending',
                'position' => (int) $project->credits()->max('position') + 1,
            ]);

            return $credit;
        }, ['project_id' => $project->id]);
    }

    /**
     * The tagged person confirms. Only now does the credit become visible — on
     * the project AND on their own portfolio, in the skill they were credited in.
     */
    public function accept(ProjectCredit $credit): ProjectCredit
    {
        $credit = $this->respond($credit, Accepted::class, ProjectCreditAccepted::class);

        $this->ensureProjectsBlock($credit);

        return $credit;
    }

    /**
     * Make sure the confirmation actually shows something.
     *
     * Accepting is a promise — "this work now appears on your profile" — and a
     * profile only renders projects where a `projects` BLOCK exists in that
     * scope (ADR-Q). Several skills do not seed one by default (photography
     * seeds a gallery, not projects), so without this the collaborator would
     * confirm a credit and see precisely nothing.
     *
     * Scope follows the same fallback as Talent::portfolioProjects(): the
     * credited skill's tab when they hold that skill, otherwise the universal
     * region — the project owner chose that skill and it need not be one the
     * collaborator lists.
     */
    private function ensureProjectsBlock(ProjectCredit $credit): void
    {
        $talent = $credit->talent;

        if ($talent === null) {
            return;
        }

        $blockType = BlockType::where('key', 'projects')->where('is_active', true)->first();

        if ($blockType === null) {
            return;
        }

        $holdsSkill = $talent->talentTypes()->whereKey($credit->talent_type_id)->exists();
        $scope = $holdsSkill ? (int) $credit->talent_type_id : null;

        $exists = $talent->profileBlocks()
            ->where('block_type_id', $blockType->getKey())
            ->when($scope === null,
                fn ($q) => $q->whereNull('talent_type_id'),
                fn ($q) => $q->where('talent_type_id', $scope))
            ->exists();

        if ($exists) {
            return;
        }

        $this->runInTransaction(function () use ($talent, $blockType, $scope): void {
            $position = (int) $talent->profileBlocks()
                ->when($scope === null,
                    fn ($q) => $q->whereNull('talent_type_id'),
                    fn ($q) => $q->where('talent_type_id', $scope))
                ->max('position');

            $talent->profileBlocks()->create([
                'block_type_id' => $blockType->getKey(),
                'talent_type_id' => $scope,
                'title' => $blockType->getTranslations('name'),
                'position' => $position + 1,
                'is_visible' => true,
                'status' => 'visible',
                'layout' => $blockType->default_layout,
                'settings' => [],
                'content' => null,
            ]);
        }, ['talent_id' => $talent->getKey(), 'scope' => $scope]);
    }

    /**
     * The tagged person refuses, or withdraws a credit they had accepted. Either
     * way it disappears from both profiles: someone who does not want to be
     * publicly associated with a project must be able to say so at any point.
     */
    public function decline(ProjectCredit $credit): ProjectCredit
    {
        return $this->respond($credit, Declined::class, ProjectCreditDeclined::class);
    }

    /**
     * The project owner removes a credit outright.
     */
    public function remove(ProjectCredit $credit): void
    {
        $this->runInTransaction(function () use ($credit): void {
            $credit->delete();
        }, ['credit_id' => $credit->id]);
    }

    /**
     * Reorder a project's credits. An id that is not this project's is a domain
     * error, so a tampered payload changes nothing.
     *
     * @param  list<int>  $orderedIds
     */
    public function reorder(Project $project, array $orderedIds): void
    {
        $this->runInTransaction(function () use ($project, $orderedIds): void {
            $owned = $project->credits()->pluck('id')->map('intval')->all();

            foreach ($orderedIds as $position => $id) {
                if (! in_array((int) $id, $owned, true)) {
                    throw new InvalidArgumentException("Credit [{$id}] is not on this project.");
                }

                ProjectCredit::whereKey($id)->update(['position' => $position]);
            }
        }, ['project_id' => $project->id]);
    }

    /**
     * Notify a freshly-created on-platform credit's subject. Called POST-COMMIT
     * by the controller, never inside the write transaction — the same rule the
     * feed notifier follows.
     */
    public function notifyRequested(ProjectCredit $credit): void
    {
        if (! $credit->isOnPlatform()) {
            return;
        }

        $credit->loadMissing(['talent', 'project.talent', 'talentType']);

        $this->guard(fn () => $credit->talent?->notify(new ProjectCreditRequested([
            'message' => ':name credited you on ":project"',
            'params' => [
                'name' => $credit->project?->talent?->display_name ?? __('Someone'),
                'project' => (string) $credit->project?->title,
            ],
            'url' => route('talent.credits'),
            'actor' => $this->actorPayload($credit->project?->talent),
        ])), $credit);
    }

    /**
     * Shared accept/decline body: transition, stamp, then tell the project owner
     * what happened — they need to know whether the credit block they published
     * still says what they think it says.
     *
     * @param  class-string  $state
     * @param  class-string  $notification
     */
    private function respond(ProjectCredit $credit, string $state, string $notification): ProjectCredit
    {
        $credit = $this->runInTransaction(function () use ($credit, $state): ProjectCredit {
            $credit->status->transitionTo($state);
            $credit->forceFill(['responded_at' => now()])->save();

            return $credit->refresh();
        }, ['credit_id' => $credit->id]);

        $credit->loadMissing(['talent', 'project.talent']);
        $owner = $credit->project?->talent;

        if ($owner !== null && ! $owner->is($credit->talent)) {
            $this->guard(fn () => $owner->notify(new $notification([
                'message' => $state === Accepted::class
                    ? ':name accepted your credit on ":project"'
                    : ':name declined your credit on ":project"',
                'params' => [
                    'name' => $credit->talent?->display_name ?? __('Someone'),
                    'project' => (string) $credit->project?->title,
                ],
                'url' => route('talent.projects'),
                'actor' => $this->actorPayload($credit->talent),
            ])), $credit);
        }

        return $credit;
    }

    /**
     * Resolve and validate the credit's shape.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: Talent|null, 1: TalentType}
     */
    private function assertShape(Project $project, array $data): array
    {
        $type = TalentType::find($data['talent_type_id'] ?? null)
            ?? throw new InvalidArgumentException(__('Pick the skill they were credited in.'));

        $talentId = $data['talent_id'] ?? null;

        if ($talentId === null || $talentId === '') {
            if (blank($data['name'] ?? null)) {
                throw new InvalidArgumentException(__('Name the collaborator, or pick one from Fama.'));
            }

            return [null, $type];
        }

        $collaborator = Talent::find($talentId)
            ?? throw new InvalidArgumentException(__('That collaborator could not be found.'));

        // The owner is already the author of the project; crediting yourself
        // would put the project in your own tabs twice.
        if ((int) $collaborator->getKey() === (int) $project->talent_id) {
            throw new InvalidArgumentException(__('You are already on this project as its owner.'));
        }

        return [$collaborator, $type];
    }

    /**
     * The actor block every notification payload carries.
     *
     * @return array<string, mixed>|null
     */
    private function actorPayload(?Talent $talent): ?array
    {
        return $talent === null ? null : [
            'type' => 'talent',
            'id' => $talent->getKey(),
            'username' => $talent->slug,
            'display_name' => $talent->display_name,
            'avatar_url' => $talent->avatar_url,
        ];
    }

    /**
     * A notification failure must never roll back or break the write that caused
     * it — the same guard the feed notifier uses, so a silent bug shows up in the
     * log rather than in the response.
     */
    private function guard(callable $callback, ProjectCredit $credit): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            Log::channel('app')->error('Project credit notification failed.', [
                'credit_id' => $credit->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
