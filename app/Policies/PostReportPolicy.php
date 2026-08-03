<?php

namespace App\Policies;

use App\Feed\Actor;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostReport;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Reports (feed-spec §10): any participating Actor may report a post or
 * comment — once per reportable per reporter (backed by the unique index).
 * Resolution is admin moderation.
 */
class PostReportPolicy extends BasePolicy
{
    /**
     * Any Actor may report a target it hasn't already reported.
     */
    public function create(Authenticatable $user, Post|Comment|null $reportable = null): bool
    {
        if (! $user instanceof Actor) {
            return false;
        }

        if ($reportable instanceof Model) {
            $already = PostReport::query()
                ->where('reportable_type', $reportable->getMorphClass())
                ->where('reportable_id', $reportable->getKey())
                ->where('reporter_type', $user->getMorphClass())
                ->where('reporter_id', $user->getKey())
                ->exists();

            if ($already) {
                return false;
            }
        }

        return true;
    }

    /**
     * Moderating admins work the reports queue.
     */
    public function moderate(User $user, ?PostReport $report = null): bool
    {
        return $user->can('moderate-content');
    }
}
