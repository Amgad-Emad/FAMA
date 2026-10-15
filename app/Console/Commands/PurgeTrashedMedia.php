<?php

namespace App\Console\Commands;

use App\Feed\PostPurger;
use App\Models\Post;
use App\Models\Talent;
use App\Services\TrashService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The media retention sweep (scheduled daily 03:50).
 *
 * A hard delete already removes a model's files (medialibrary's deleting hook).
 * A SOFT delete keeps them on purpose — the row can be restored (an author's
 * deleted post, an admin-deleted talent/brand/review). This command finishes
 * the job once the restore window is over:
 *
 *  1. files of soft-deleted owners trashed more than N days ago are deleted
 *     (config media-library.trash_retention_days, default 30; the row stays
 *     trashed, so the audit trail and counters are untouched);
 *  0. posts trashed past the window are purged for good — comments, likes,
 *     saves, mentions, links and files included (App\Feed\PostPurger);
 *  2. orphans — media whose owner row no longer exists at all (e.g. removed by
 *     a database cascade, which fires no model events) — are deleted.
 */
class PurgeTrashedMedia extends Command
{
    protected $signature = 'media:purge-trashed {--days= : Restore window in days (default: config)} {--dry-run : Only report}';

    protected $description = 'Delete files of models trashed past the restore window, and orphaned media';

    /** Run both sweeps; report counts. */
    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('media-library.trash_retention_days', 30));
        $dry = (bool) $this->option('dry-run');
        $cutoff = now()->subDays($days);
        $trashed = 0;
        $orphans = 0;

        // 0. Posts trashed past the window go for good, with their comments,
        //    likes, saves, mentions, links and files (PostPurger).
        $posts = 0;
        Post::onlyTrashed()->where('deleted_at', '<', $cutoff)->chunkById(100, function ($items) use (&$posts, $dry) {
            foreach ($items as $post) {
                $posts++;
                $dry || app(PostPurger::class)->purge($post);
            }
        });

        // 0b. Talents trashed past the window go for good too — with their
        //     projects (co-owned ones pass to a co-owner), profile content,
        //     posts and every file (TrashService, the same path as the admin's
        //     "Delete forever"). Their projects/content are not trashed rows
        //     themselves, so the media step below would never reach them.
        $talents = 0;
        foreach (Talent::onlyTrashed()->where('deleted_at', '<', $cutoff)->pluck('id') as $talentId) {
            $talents++;
            $dry || app(TrashService::class)->purge(null, 'talents', (int) $talentId);
        }

        $types = Media::query()->distinct()->pluck('model_type');

        foreach ($types as $type) {
            $class = Relation::getMorphedModel($type) ?? $type;
            if (! class_exists($class)) {
                continue;
            }
            $model = new $class;
            $table = $model->getTable();
            $key = $model->getKeyName();
            $soft = in_array(SoftDeletes::class, class_uses_recursive($class), true);

            // 2. Orphans: the owner row is gone entirely.
            Media::query()->where('model_type', $type)
                ->whereNotExists(fn ($q) => $q->selectRaw('1')->from($table)->whereColumn("{$table}.{$key}", 'media.model_id'))
                ->chunkById(200, function ($items) use (&$orphans, $dry) {
                    foreach ($items as $media) {
                        $orphans++;
                        $dry || $media->delete();
                    }
                });

            // 1. Owners in the trash past the restore window.
            if ($soft) {
                $deletedAt = $model->getQualifiedDeletedAtColumn();
                Media::query()->where('model_type', $type)
                    ->whereExists(fn ($q) => $q->selectRaw('1')->from($table)
                        ->whereColumn("{$table}.{$key}", 'media.model_id')
                        ->whereNotNull($deletedAt)->where($deletedAt, '<', $cutoff))
                    ->chunkById(200, function ($items) use (&$trashed, $dry) {
                        foreach ($items as $media) {
                            $trashed++;
                            $dry || $media->delete();
                        }
                    });
            }
        }

        $verb = $dry ? 'Would delete' : 'Deleted';
        $this->info("{$verb} {$talents} talent(s) and {$posts} post(s) for good, {$trashed} file(s) of items trashed over {$days} days, and {$orphans} orphaned file(s).");
        if (! $dry && ($talents || $posts || $trashed || $orphans)) {
            Log::channel('media')->info('media:purge-trashed', ['talents' => $talents, 'posts' => $posts, 'trashed' => $trashed, 'orphans' => $orphans, 'days' => $days]);
        }

        return self::SUCCESS;
    }
}
