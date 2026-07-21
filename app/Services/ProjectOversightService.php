<?php

namespace App\Services;

use App\Models\BrandProject;
use App\Models\User;
use App\States\BrandProject\Cancelled;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Admin project oversight (Phase 3A). Filter projects by status, and intervene
 * (cancel / force-private). Gated on `moderate-content`; interventions are
 * transactional + activity-logged with the admin as causer.
 */
class ProjectOversightService extends AdminService
{
    /**
     * Paginated project list for oversight, optionally filtered by status and a
     * free-text search over the title / brand name.
     */
    public function forStatus(User $admin, ?string $status = null, ?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        $this->authorizeAdmin($admin, 'oversee', BrandProject::class);

        return BrandProject::query()
            ->with(['brand', 'media'])
            ->withCount('contracts')
            ->when($status !== null, fn ($q) => $q->where('status', $status))
            ->when($search, fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$term}%")
                ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$term}%"))))
            ->latest()
            ->paginate($perPage);
    }

    public function cancel(User $admin, BrandProject $project, ?string $reason = null): BrandProject
    {
        $this->authorizeAdmin($admin, 'oversee', $project);

        return $this->runInTransaction(function () use ($admin, $project, $reason): BrandProject {
            if ($project->status->canTransitionTo(Cancelled::class)) {
                $project->status->transitionTo(Cancelled::class);
            }
            $this->record($admin, $project, 'moderation', 'project.cancelled_by_admin', ['reason' => $reason]);

            return $project->refresh();
        }, ['brand_project_id' => $project->getKey()]);
    }

    public function forcePrivate(User $admin, BrandProject $project, ?string $reason = null): BrandProject
    {
        $this->authorizeAdmin($admin, 'oversee', $project);

        return $this->runInTransaction(function () use ($admin, $project, $reason): BrandProject {
            $project->update(['is_public' => false]);
            $this->record($admin, $project, 'moderation', 'project.forced_private', ['reason' => $reason]);

            return $project->refresh();
        }, ['brand_project_id' => $project->getKey()]);
    }

    /**
     * The reverse of forcePrivate: re-list a project publicly. Together they
     * form the state-aware visibility toggle in the oversight queue.
     */
    public function makePublic(User $admin, BrandProject $project, ?string $reason = null): BrandProject
    {
        $this->authorizeAdmin($admin, 'oversee', $project);

        return $this->runInTransaction(function () use ($admin, $project, $reason): BrandProject {
            $project->update(['is_public' => true]);
            $this->record($admin, $project, 'moderation', 'project.made_public', ['reason' => $reason]);

            return $project->refresh();
        }, ['brand_project_id' => $project->getKey()]);
    }
}
