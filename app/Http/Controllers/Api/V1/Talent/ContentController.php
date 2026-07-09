<?php

namespace App\Http\Controllers\Api\V1\Talent;

use App\Support\Talent\BlockContentRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Talent · Content
 *
 * @authenticated
 *
 * The talent's repeatable content child tables, one endpoint set per `{type}`:
 * gallery, look_types, digitals, showreel, equipment, projects (case studies),
 * software_stack, brand_collabs. Driven by the shared BlockContentRegistry so the
 * field set, validation and serialization match the web dashboard exactly.
 * Uploaded assets go through medialibrary; the upload response carries the
 * conversion URL.
 *
 * @urlParam type string required The content type. Example: gallery
 */
class ContentController extends TalentApiController
{
    /**
     * List content items
     *
     * Paginated rows for `{type}`, in display order.
     */
    public function index(string $type): JsonResponse
    {
        $config = BlockContentRegistry::config($type);
        $paginator = $this->talent()->{$config['relation']}()->orderBy('position')->paginate(24);

        return response()->paginated(
            $paginator,
            $paginator->getCollection()->map(fn (Model $item) => BlockContentRegistry::serialize($item, $config))->all(),
        );
    }

    /**
     * Create a content item
     */
    public function store(Request $request, string $type): JsonResponse
    {
        $config = BlockContentRegistry::config($type);
        $data = $request->validate(BlockContentRegistry::rules($config));
        // `position` is NOT NULL — append at the end when the client omits it.
        $data['position'] = $data['position'] ?? $this->talent()->{$config['relation']}()->count();
        $item = $this->talent()->{$config['relation']}()->create($data);

        return response()->success(BlockContentRegistry::serialize($item, $config), __('Added.'), status: 201);
    }

    /**
     * Update a content item
     */
    public function update(Request $request, string $type, int $id): JsonResponse
    {
        $config = BlockContentRegistry::config($type);
        $item = $this->find($config, $id);
        $item->update($request->validate(BlockContentRegistry::rules($config)));

        return response()->success(BlockContentRegistry::serialize($item->fresh(), $config), __('Saved.'));
    }

    /**
     * Delete a content item
     */
    public function destroy(string $type, int $id): JsonResponse
    {
        $config = BlockContentRegistry::config($type);
        $this->find($config, $id)->delete();

        return response()->success(null, __('Removed.'));
    }

    /**
     * Reorder content items
     *
     * @bodyParam order integer[] required The item ids in the desired order. Example: [3,1,2]
     */
    public function reorder(Request $request, string $type): JsonResponse
    {
        $config = BlockContentRegistry::config($type);
        $validated = $request->validate(['order' => ['required', 'array'], 'order.*' => ['integer']]);

        DB::transaction(function () use ($config, $validated): void {
            $owned = array_map('intval', $this->talent()->{$config['relation']}()->pluck('id')->all());
            foreach ($validated['order'] as $index => $id) {
                abort_unless(in_array((int) $id, $owned, true), 403);
                $this->talent()->{$config['relation']}()->whereKey($id)->update(['position' => $index]);
            }
        });

        return response()->success(null, __('Order saved.'));
    }

    /**
     * Upload media for a content item
     *
     * Multipart `file` (image/video). Returns the item with its conversion URL.
     *
     * @bodyParam file file required The asset to upload.
     */
    public function uploadMedia(Request $request, string $type, int $id): JsonResponse
    {
        $config = BlockContentRegistry::config($type);
        abort_if($config['media'] === null, 422, __('This block does not accept media.'));
        $request->validate(['file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,mp4,mov', 'max:20480']]);

        $item = $this->find($config, $id);
        $item->addMedia($request->file('file'))->toMediaCollection($config['media']);

        return response()->success(BlockContentRegistry::serialize($item->fresh(), $config), __('Uploaded.'));
    }

    /**
     * Resolve and ownership-check a row for `{type}`.
     *
     * @param  array<string, mixed>  $config
     */
    private function find(array $config, int $id): Model
    {
        $item = $config['model']::findOrFail($id);
        $this->ensureOwns($item);

        return $item;
    }
}
