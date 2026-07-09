<?php

namespace App\Http\Controllers\Talent;

use App\Support\Talent\BlockContentRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Block content editors (talent-spec) — the child content tables surfaced from
 * the profile editor. Driven by App\Support\Talent\BlockContentRegistry (shared
 * with the mobile API) so one controller serves every "table" block (gallery,
 * digitals, showreel, equipment, projects, software, brand collabs, looks).
 * Uploaded assets go through medialibrary. (Inline-JSON blocks are edited on the
 * profile editor via ProfileBlockService; comp_card is a 1:1 singleton handled
 * separately.)
 */
class BlockContentController extends TalentController
{
    /**
     * @return array<string, mixed>
     */
    private function config(string $type): array
    {
        return BlockContentRegistry::config($type);
    }

    public function index(string $type): View
    {
        $config = $this->config($type);

        return view('talent.content', [
            'type' => $type,
            'config' => [
                'label' => $config['label'],
                'media' => $config['media'],
                'fields' => array_map(fn ($f) => ['name' => $f[0], 'kind' => $f[1], 'options' => $f[2]], $config['fields']),
            ],
            'types' => BlockContentRegistry::types(),
        ]);
    }

    public function data(string $type): JsonResponse
    {
        $config = $this->config($type);
        $paginator = $this->talent()->{$config['relation']}()->orderBy('position')->paginate(24);

        return response()->paginated(
            $paginator,
            $paginator->getCollection()->map(fn (Model $item) => BlockContentRegistry::present($item, $config))->all(),
        );
    }

    public function store(Request $request, string $type): JsonResponse
    {
        $config = $this->config($type);
        $data = $request->validate(BlockContentRegistry::rules($config));
        // Append at the end when no position is given (the front-end blank sends
        // null); `position` is a NOT NULL column, so never insert null.
        $data['position'] = $data['position'] ?? $this->talent()->{$config['relation']}()->count();
        $item = $this->talent()->{$config['relation']}()->create($data);

        return response()->success(BlockContentRegistry::present($item, $config), __('Added.'), status: 201);
    }

    public function update(Request $request, string $type, int $id): JsonResponse
    {
        $config = $this->config($type);
        $item = $this->find($config, $id);
        $item->update($request->validate(BlockContentRegistry::rules($config)));

        return response()->success(BlockContentRegistry::present($item, $config), __('Saved.'));
    }

    public function destroy(string $type, int $id): JsonResponse
    {
        $config = $this->config($type);
        $this->find($config, $id)->delete();

        return response()->success(null, __('Removed.'));
    }

    public function reorder(Request $request, string $type): JsonResponse
    {
        $config = $this->config($type);
        $validated = $request->validate(['order' => ['required', 'array'], 'order.*' => ['integer']]);

        DB::transaction(function () use ($config, $validated): void {
            $owned = $this->talent()->{$config['relation']}()->pluck('id')->all();
            foreach ($validated['order'] as $index => $id) {
                abort_unless(in_array((int) $id, array_map('intval', $owned), true), 403);
                $this->talent()->{$config['relation']}()->whereKey($id)->update(['position' => $index]);
            }
        });

        return response()->success(null, __('Order saved.'));
    }

    public function uploadMedia(Request $request, string $type, int $id): JsonResponse
    {
        $config = $this->config($type);
        abort_if($config['media'] === null, 422, 'This block does not accept media.');
        $request->validate(['file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,mp4,mov', 'max:20480']]);

        $item = $this->find($config, $id);
        $item->addMedia($request->file('file'))->toMediaCollection($config['media']);

        return response()->success(BlockContentRegistry::present($item->fresh(), $config), __('Uploaded.'));
    }

    // ----- Internal ----------------------------------------------------------

    /**
     * @param  array<string, mixed>  $config
     */
    private function find(array $config, int $id): Model
    {
        $item = $config['model']::findOrFail($id);
        $this->ensureOwns($item);

        return $item;
    }
}
