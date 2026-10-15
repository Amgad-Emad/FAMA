<?php

namespace App\Http\Controllers\Admin;

use App\Services\TrashService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * The admin Trash (moderate-content to see and restore; manage-settings to
 * delete forever). Rules live in TrashService.
 */
class TrashController extends AdminController
{
    public function __construct(private readonly TrashService $trash) {}

    /** The page shell: types + their counts. ?type= deep-links a tab. */
    public function index(Request $request): View
    {
        $types = array_keys($this->trash->types());
        $type = in_array($request->query('type'), $types, true) ? $request->query('type') : $types[0];

        return view('admin.trash.index', [
            'type' => $type,
            'counts' => $this->trash->counts(),
            'canPurge' => $this->admin()->can('manage-settings'),
            'retention' => (int) config('media-library.trash_retention_days', 30),
        ]);
    }

    /** One type's trashed rows (paginated, searchable). */
    public function data(Request $request, string $type): JsonResponse
    {
        abort_unless(array_key_exists($type, $this->trash->types()), 404);
        $page = $this->trash->query($type, $request->string('q')->trim()->value() ?: null)->paginate(20);

        return response()->paginated($page, $page->getCollection()->map(fn ($row) => $this->trash->present($type, $row))->values(),
            meta: ['counts' => $this->trash->counts()]);
    }

    /** Restore one item. */
    public function restore(string $type, int $id): JsonResponse
    {
        try {
            $this->trash->restore($this->admin(), $type, $id);
        } catch (InvalidArgumentException $e) {
            return response()->error($e->getMessage(), status: 422);
        }

        return response()->success(null, __('Restored.'));
    }

    /** Delete one item forever (manage-settings). */
    public function destroy(string $type, int $id): JsonResponse
    {
        abort_unless($this->admin()->can('manage-settings'), 403);
        $this->trash->purge($this->admin(), $type, $id);

        return response()->success(null, __('Deleted forever.'));
    }

    /** Bulk restore / delete forever (≤200; impossible items skipped). */
    public function bulk(Request $request, string $type): JsonResponse
    {
        abort_unless(array_key_exists($type, $this->trash->types()), 404);
        $data = $request->validate([
            'action' => ['required', 'in:restore,purge'],
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer'],
        ]);
        abort_if($data['action'] === 'purge' && ! $this->admin()->can('manage-settings'), 403);

        [$done, $skipped] = $this->trash->bulk($this->admin(), $type, $data['action'], array_map('intval', $data['ids']));

        return response()->success(['done' => $done, 'skipped' => $skipped], __('Done.'));
    }
}
