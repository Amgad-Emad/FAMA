<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Recognition;
use App\Models\Review;
use App\Models\Talent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bulk actions on the moderation queues: delete many rows at once, and export
 * rows (the selected ones, or everything matching the current search/filter)
 * as CSV.
 *
 * Delete goes through the SAME per-row paths as the single actions — talents
 * and brands via their moderation services (authorized + audited), reviews and
 * recognitions as audited soft deletes — so a bulk delete is exactly N single
 * deletes. Soft deletes keep files for the restore window; media:purge-trashed
 * removes them after it.
 */
class ModerationBulkService extends Service
{
    /** Queues that support bulk delete / export. */
    public const QUEUES = ['talents', 'brands', 'reviews', 'recognitions'];

    /** Rows one bulk delete may touch. */
    public const MAX = 200;

    public function __construct(
        private readonly TalentModerationService $talents,
        private readonly BrandModerationService $brands,
    ) {}

    /**
     * Soft-delete the given rows of a queue. Returns how many were deleted.
     *
     * @param  list<int>  $ids
     */
    public function delete(User $admin, string $queue, array $ids): int
    {
        $ids = array_slice(array_values(array_unique(array_map('intval', $ids))), 0, self::MAX);

        return $this->runInTransaction(function () use ($admin, $queue, $ids): int {
            $done = 0;
            foreach ($this->model($queue)::query()->whereKey($ids)->get() as $row) {
                match ($queue) {
                    'talents' => $this->talents->softDelete($admin, $row, __('Bulk delete')),
                    'brands' => $this->brands->softDelete($admin, $row, __('Bulk delete')),
                    default => $this->auditedDelete($admin, $row, $queue === 'reviews' ? 'review.deleted' : 'recognition.deleted'),
                };
                $done++;
            }

            return $done;
        }, ['queue' => $queue, 'count' => count($ids)]);
    }

    /**
     * Stream a CSV of a queue: the given ids, or every row matching the list's
     * own filters (q / status) when none are given.
     *
     * @param  list<int>  $ids
     */
    public function export(string $queue, array $ids, Request $request): StreamedResponse
    {
        $query = $this->query($queue, $request);
        if ($ids !== []) {
            $query->whereKey(array_map('intval', $ids));
        }
        [$headers, $row] = $this->columns($queue);
        $file = sprintf('fama-%s-%s.csv', $queue, now()->format('Y-m-d-His'));

        return response()->streamDownload(function () use ($query, $headers, $row) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads Arabic names right
            fputcsv($out, $headers);
            $query->chunkById(500, function ($items) use ($out, $row) {
                foreach ($items as $item) {
                    fputcsv($out, array_map([$this, 'safeCell'], $row($item)));
                }
            });
            fclose($out);
        }, $file, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * The list query for a queue, with the same q / status filters the queue
     * endpoints apply (ModerationController), oldest-safe for chunking.
     */
    public function query(string $queue, Request $request): Builder
    {
        $q = $request->query('q');
        $status = $request->query('status');

        return match ($queue) {
            'talents' => Talent::query()->withTrashed()->with('talentTypes')
                ->when($q, fn ($b) => $b->where(fn ($w) => $w->where('display_name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")->orWhere('slug', 'like', "%{$q}%")))
                ->when($status, fn ($b) => $b->where('status', $status)),
            'brands' => Brand::query()->withTrashed()
                ->when($q, fn ($b) => $b->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")->orWhere('slug', 'like', "%{$q}%")))
                ->when($status, fn ($b) => $b->where('status', $status)),
            'reviews' => Review::query()->where('status', 'pending')->with('talent')
                ->when($q, fn ($b) => $b->where(fn ($w) => $w->where('body', 'like', "%{$q}%")->orWhere('reviewer_name', 'like', "%{$q}%"))),
            'recognitions' => Recognition::query()->where('status', 'pending')->with(['talent', 'author'])
                ->when($q, fn ($b) => $b->where('body', 'like', "%{$q}%")),
            default => abort(404),
        };
    }

    /**
     * CSV headers + a row mapper per queue.
     *
     * @return array{0: list<string>, 1: callable}
     */
    private function columns(string $queue): array
    {
        $date = fn ($d) => $d?->format('Y-m-d H:i');

        return match ($queue) {
            'talents' => [
                ['ID', 'Name', 'Username', 'Email', 'Status', 'Verified', 'Live', 'Skills', 'City', 'Country', 'Six steps done', 'Joined', 'Deleted'],
                fn (Talent $t) => [$t->id, $t->display_name, $t->slug, $t->email, $t->status->getValue(), $t->is_verified ? 'yes' : 'no', $t->is_published ? 'yes' : 'no',
                    $t->talentTypes->map(fn ($s) => $s->getTranslation('name', 'en'))->implode(', '), $t->base_city, $t->base_country,
                    $t->steps_completed_at ? 'yes' : 'no', $date($t->created_at), $date($t->deleted_at)],
            ],
            'brands' => [
                ['ID', 'Name', 'Slug', 'Email', 'Status', 'Verified', 'Published', 'City', 'Joined', 'Deleted'],
                fn (Brand $b) => [$b->id, $b->name, $b->slug, $b->email, $b->status->getValue(), $b->is_verified ? 'yes' : 'no', $b->is_published ? 'yes' : 'no', $b->base_city, $date($b->created_at), $date($b->deleted_at)],
            ],
            'reviews' => [
                ['ID', 'About talent', 'Reviewer', 'Rating', 'Review', 'Status', 'Written'],
                fn (Review $r) => [$r->id, $r->talent?->display_name, $r->reviewer_name, $r->rating, $r->body, $r->status->getValue(), $date($r->created_at)],
            ],
            'recognitions' => [
                ['ID', 'About talent', 'By', 'Relationship', 'Recognition', 'Status', 'Written'],
                fn (Recognition $r) => [$r->id, $r->talent?->display_name, $r->author?->display_name, $r->relationship, $r->body, $r->status->getValue(), $date($r->created_at)],
            ],
            default => abort(404),
        };
    }

    /** The model class behind a queue. */
    private function model(string $queue): string
    {
        return match ($queue) {
            'talents' => Talent::class,
            'brands' => Brand::class,
            'reviews' => Review::class,
            'recognitions' => Recognition::class,
            default => abort(404),
        };
    }

    /** Soft-delete with an audit entry (reviews, recognitions). */
    private function auditedDelete(User $admin, Review|Recognition $row, string $event): void
    {
        activity('moderation')->causedBy($admin)->performedOn($row)->withProperties(['reason' => __('Bulk delete')])->log($event);
        $row->delete();
    }

    /**
     * Neutralise spreadsheet formula injection: a cell starting with = + - @
     * (or a tab/CR) is prefixed with an apostrophe.
     */
    public function safeCell(mixed $value): mixed
    {
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
