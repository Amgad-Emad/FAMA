<?php

namespace App\Http\Controllers\Talent;

use App\Models\BrandCollab;
use App\Models\CaseStudy;
use App\Models\Digital;
use App\Models\Equipment;
use App\Models\LookType;
use App\Models\PortfolioItem;
use App\Models\Showreel;
use App\Models\SoftwareStack;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Block content editors (talent-spec) — the child content tables surfaced from
 * the profile editor. Driven by a per-type registry so one controller serves
 * every "table" block (gallery, digitals, showreel, equipment, case studies,
 * software, brand collabs, looks). Uploaded assets go through medialibrary.
 * (Inline-JSON blocks are edited on the profile editor via ProfileBlockService;
 * comp_card is a 1:1 singleton handled separately.)
 */
class BlockContentController extends TalentController
{
    /**
     * @return array<string, array<string, mixed>>
     */
    private function registry(): array
    {
        return [
            'gallery' => ['model' => PortfolioItem::class, 'relation' => 'portfolioItems', 'label' => 'Gallery', 'labelField' => 'caption', 'media' => 'gallery', 'thumb' => 'thumbnail_url', 'fields' => [
                ['caption', 'translatable', []],
                ['media_type', 'select', ['image', 'video', 'embed']],
                ['embed_url', 'url', []],
            ]],
            'look_types' => ['model' => LookType::class, 'relation' => 'lookTypes', 'label' => 'Looks', 'labelField' => 'name', 'media' => null, 'thumb' => null, 'fields' => [
                ['name', 'translatable', ['required']],
            ]],
            'digitals' => ['model' => Digital::class, 'relation' => 'digitals', 'label' => 'Digitals', 'labelField' => 'shot_type', 'media' => 'digital', 'thumb' => 'thumbnail_url', 'fields' => [
                ['shot_type', 'select', ['front', 'side', 'back', 'full', 'headshot', 'smile']],
                ['captured_at', 'date', []],
            ]],
            'showreel' => ['model' => Showreel::class, 'relation' => 'showreels', 'label' => 'Showreel', 'labelField' => 'title', 'media' => 'thumbnail', 'thumb' => 'thumbnail_url', 'fields' => [
                ['title', 'translatable', []],
                ['video_url', 'url', ['required']],
                ['platform', 'select', ['youtube', 'vimeo', 'self_hosted']],
                ['duration_seconds', 'number', []],
            ]],
            'equipment' => ['model' => Equipment::class, 'relation' => 'equipment', 'label' => 'Equipment', 'labelField' => 'name', 'media' => null, 'thumb' => null, 'fields' => [
                ['category', 'select', ['camera', 'lens', 'lighting', 'audio', 'grip', 'drone', 'accessory']],
                ['brand', 'text', []],
                ['model', 'text', []],
                ['name', 'text', ['required']],
                ['notes', 'translatable', []],
            ]],
            'case_studies' => ['model' => CaseStudy::class, 'relation' => 'caseStudies', 'label' => 'Projects', 'labelField' => 'title', 'media' => 'cover', 'thumb' => 'cover_image_url', 'fields' => [
                ['title', 'translatable', ['required']],
                ['client_name', 'text', []],
                ['summary', 'translatable', []],
                ['body', 'translatable', []],
                ['year', 'number', []],
                ['url', 'url', []],
            ]],
            'software_stack' => ['model' => SoftwareStack::class, 'relation' => 'softwareStack', 'label' => 'Software', 'labelField' => 'software_name', 'media' => 'icon', 'thumb' => 'icon_url', 'fields' => [
                ['software_name', 'text', ['required']],
                ['proficiency', 'select', ['beginner', 'intermediate', 'advanced', 'expert']],
            ]],
            'brand_collabs' => ['model' => BrandCollab::class, 'relation' => 'brandCollabs', 'label' => 'Brand collabs', 'labelField' => 'brand_name', 'media' => 'logo', 'thumb' => 'brand_logo_url', 'fields' => [
                ['brand_name', 'text', ['required']],
                ['project_title', 'translatable', []],
                ['year', 'number', []],
                ['url', 'url', []],
            ]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function config(string $type): array
    {
        return $this->registry()[$type] ?? abort(404);
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
            'types' => collect($this->registry())->map(fn ($c, $k) => ['type' => $k, 'label' => $c['label']])->values(),
        ]);
    }

    public function data(string $type): JsonResponse
    {
        $config = $this->config($type);
        $paginator = $this->talent()->{$config['relation']}()->orderBy('position')->paginate(24);

        return response()->paginated(
            $paginator,
            $paginator->getCollection()->map(fn (Model $item) => $this->present($item, $config))->all(),
        );
    }

    public function store(Request $request, string $type): JsonResponse
    {
        $config = $this->config($type);
        $data = $request->validate($this->rules($config));
        // Append at the end when no position is given (the front-end blank sends
        // null); `position` is a NOT NULL column, so never insert null.
        $data['position'] = $data['position'] ?? $this->talent()->{$config['relation']}()->count();
        $item = $this->talent()->{$config['relation']}()->create($data);

        return response()->success($this->present($item, $config), __('Added.'), status: 201);
    }

    public function update(Request $request, string $type, int $id): JsonResponse
    {
        $config = $this->config($type);
        $item = $this->find($config, $id);
        $item->update($request->validate($this->rules($config)));

        return response()->success($this->present($item, $config), __('Saved.'));
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

        return response()->success($this->present($item->fresh(), $config), __('Uploaded.'));
    }

    // ----- Internal ----------------------------------------------------------

    private function find(array $config, int $id): Model
    {
        $item = $config['model']::findOrFail($id);
        $this->ensureOwns($item);

        return $item;
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(array $config): array
    {
        $rules = ['position' => ['nullable', 'integer']];

        foreach ($config['fields'] as [$name, $kind, $options]) {
            match ($kind) {
                'translatable' => $rules = $rules + [
                    $name => ['array'],
                    "$name.en" => [in_array('required', $options, true) ? 'required' : 'nullable', 'string', 'max:5000'],
                    "$name.ar" => ['nullable', 'string', 'max:5000'],
                ],
                'select' => $rules[$name] = ['nullable', Rule::in($options)],
                'number' => $rules[$name] = ['nullable', 'integer'],
                'url' => $rules[$name] = [in_array('required', $options, true) ? 'required' : 'nullable', 'url', 'max:2048'],
                'date' => $rules[$name] = ['nullable', 'date'],
                default => $rules[$name] = [in_array('required', $options, true) ? 'required' : 'nullable', 'string', 'max:255'],
            };
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Model $item, array $config): array
    {
        $labelField = $config['labelField'];
        $translatable = collect($config['fields'])->filter(fn ($f) => $f[1] === 'translatable')->pluck(0)->all();

        $label = in_array($labelField, $translatable, true)
            ? ($item->getTranslation($labelField, app()->getLocale()) ?: '')
            : (string) ($item->getAttribute($labelField) ?? '');

        return [
            'id' => $item->id,
            'position' => (int) $item->position,
            'label' => $label,
            'thumb' => $config['thumb'] ? $item->{$config['thumb']} : null,
        ];
    }
}
