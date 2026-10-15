<?php

namespace App\Http\Controllers\Talent;

use App\Models\ProfileBlock;
use App\Models\TalentType;
use App\Support\ContentRegistry;
use App\Support\Features;
use App\Support\Html\BriefSanitizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Block content editors (talent-spec) — the child content tables surfaced from
 * the profile editor. Driven by a per-type registry so one controller serves
 * every "table" block (gallery, digitals, showreel, equipment, software, brand
 * collabs, looks — projects have their own page, ADR-X). Uploaded assets go
 * through medialibrary.
 * (Inline-JSON blocks are edited on the profile editor via ProfileBlockService;
 * comp_card is a 1:1 singleton handled separately.)
 */
class BlockContentController extends TalentController
{
    /**
     * @return array<string, mixed>
     */
    private function config(string $type): array
    {
        return ContentRegistry::get($type) ?? abort(404);
    }

    /**
     * The profile-content page: ONE section at a time, with every content block
     * in that section stacked down the page. Sections are the profile's own
     * shape (ADR-Q/ADR-R) — the universal (profile-wide) scope plus one per
     * skill — so editing content follows the same mental model as the editor
     * and the public profile.
     *
     * `{section}` is a section key (`universal` or a skill slug). A legacy
     * content-type key (`/talent/content/gallery`) still resolves, to the first
     * section that shows that block, so old links and bookmarks keep working —
     * and `/talent/content/projects` redirects to the Projects page (ADR-X).
     */
    public function index(?string $section = null): View|RedirectResponse
    {
        if ($section === 'projects') {
            return redirect()->route('talent.projects');
        }

        $sections = $this->sections();
        $active = $this->resolveSection($sections, $section);

        return view('talent.content', [
            'sections' => array_map(fn (array $s) => Arr::except($s, ['panels']), $sections),
            'active' => $active['key'],
            'activeLabel' => $active['label'],
            'panels' => $active['panels'],
        ]);
    }

    /**
     * Every section of this talent's profile, in the talent's own section order
     * (`meta.universal_position` decides where the universal slot sits among the
     * skills), each carrying its editable panels in block order.
     *
     * A panel is one block: `content` for the table-backed types this controller
     * serves, `link` for a block authored elsewhere (projects — ADR-X),
     * `reviews` for the (read-only) reviews block — reviews live on the
     * profile like any other block, so they are edited/read here too. Blocks
     * with no editor at all (comp_card) are skipped.
     *
     * @return list<array{key: string, label: string, icon: ?string, is_primary: bool, count: int, panels: list<array<string, mixed>>}>
     */
    private function sections(): array
    {
        $locale = app()->getLocale();
        $talent = $this->talent()->loadMissing(['profileBlocks.blockType', 'talentTypes']);

        // The panels of one scope (NULL = universal), in block order.
        $panelsFor = fn (?int $typeId) => $talent->profileBlocks
            ->filter(fn (ProfileBlock $b) => $b->talent_type_id === $typeId)
            ->sortBy('position')
            ->map(fn (ProfileBlock $b) => $this->panel($b, $locale))
            ->filter()
            ->values()
            ->all();

        $sections = $talent->talentTypes
            ->sortBy(fn (TalentType $t) => [(int) $t->pivot->position, (int) $t->id])
            ->map(fn (TalentType $t) => $this->section(
                (string) $t->slug,
                $t->getTranslation('name', $locale, false) ?: $t->getTranslation('name', 'en', false),
                $panelsFor($t->id),
                icon: $t->icon,
                isPrimary: (bool) $t->pivot->is_primary,
            ))
            ->values()
            ->all();

        $position = (int) ($talent->meta['universal_position'] ?? 0);
        array_splice($sections, max(0, min($position, count($sections))), 0, [
            $this->section('universal', __('Profile-wide'), $panelsFor(null)),
        ]);

        return $sections;
    }

    /**
     * @param  list<array<string, mixed>>  $panels
     * @return array<string, mixed>
     */
    private function section(string $key, string $label, array $panels, ?string $icon = null, bool $isPrimary = false): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'icon' => $icon,
            'is_primary' => $isPrimary,
            'count' => count($panels),
            'panels' => $panels,
        ];
    }

    /**
     * One block rendered as an editable panel — or null when the block type has
     * no editor (comp_card, and any inline block edited on the profile editor).
     *
     * @return array<string, mixed>|null
     */
    private function panel(ProfileBlock $block, string $locale): ?array
    {
        $key = $block->blockType->key;
        $title = $block->getTranslation('title', $locale, false);

        // Reviews follow the public profile: off while features.reviews is off.
        if ($key === 'reviews' && ! Features::enabled('reviews')) {
            return null;
        }

        if ($key === 'reviews') {
            return ['kind' => 'reviews', 'type' => $key, 'title' => $title ?: __('Reviews'), 'is_visible' => (bool) $block->is_visible];
        }

        // Recognitions are written by peers and moderated by Fama: the talent
        // reads them here (every status), never edits them.
        if ($key === 'recognitions') {
            return [
                'kind' => 'recognitions',
                'type' => $key,
                'title' => $title ?: __('Recognitions'),
                'is_visible' => (bool) $block->is_visible,
                'items' => $this->talent()->recognitions()->with('author')->latest('id')->get()
                    ->map(fn ($r) => [
                        'status' => (string) $r->status,
                        'body' => (string) $r->body,
                        'who' => collect([$r->author?->display_name, $r->relationship])->filter()->implode(' · '),
                    ])->all(),
            ];
        }

        // Willing to explore is set on Profile structure (structured skill +
        // speciality picks), so this panel only points there.
        if ($key === 'interests') {
            return [
                'kind' => 'link',
                'type' => $key,
                'title' => $title ?: __('Willing to explore'),
                'is_visible' => (bool) $block->is_visible,
                'url' => route('talent.profile.edit').'#explore',
                'blurb' => __('Skills you\'d like to be asked about. You set these in Profile structure.'),
                'cta' => __('Edit in Profile structure'),
            ];
        }

        // Projects are authored on their own page (ADR-X); this panel is the
        // signpost, never a second editor.
        if ($key === 'projects') {
            return [
                'kind' => 'link',
                'type' => $key,
                'title' => $title ?: __('Projects'),
                'is_visible' => (bool) $block->is_visible,
                'url' => route('talent.projects'),
                'blurb' => __(':n projects tagged with this skill. Your photos and videos live inside each project.', [
                    'n' => $block->talent_type_id
                        ? $this->talent()->projects()->inSkillScope($block->talent_type_id)->count()
                        : $this->talent()->projects()->withoutSkill()->count(),
                ]),
                'cta' => __('Open Projects'),
            ];
        }

        $config = ContentRegistry::get($key);

        if ($config === null) {
            return null;
        }

        return [
            'kind' => 'content',
            'type' => $key,
            'title' => $title ?: __($config['label']),
            'is_visible' => (bool) $block->is_visible,
            'config' => [
                'label' => $config['label'],
                'media' => $config['media'],
                'fields' => array_map(fn ($f) => ['name' => $f[0], 'kind' => $f[1], 'options' => $f[2], 'rich' => $f[3] ?? false], $config['fields']),
            ],
        ];
    }

    /**
     * Resolve the requested section: a section key, a legacy content-type key
     * (→ the first section showing that block), or nothing (→ the first
     * section). An unknown key 404s rather than silently showing something else.
     *
     * @param  list<array<string, mixed>>  $sections
     * @return array<string, mixed>
     */
    private function resolveSection(array $sections, ?string $requested): array
    {
        if ($requested === null) {
            return $sections[0] ?? abort(404);
        }

        foreach ($sections as $section) {
            if ($section['key'] === $requested) {
                return $section;
            }
        }

        if (ContentRegistry::has($requested)) {
            foreach ($sections as $section) {
                if (in_array($requested, array_column($section['panels'], 'type'), true)) {
                    return $section;
                }
            }

            // The type is real but sits on no block — show the first section
            // rather than 404ing a link the editor may still hold.
            return $sections[0] ?? abort(404);
        }

        abort(404);
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
        $data = $this->dropBlankSelects($this->sanitizeRich($request->validate($this->rules($config)), $config), $config);
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
        $item->update($this->dropBlankSelects($this->sanitizeRich($request->validate($this->rules($config)), $config), $config));

        return response()->success($this->present($item, $config), __('Saved.'));
    }

    /**
     * Drop select fields submitted blank. Every select-backed column (equipment
     * category, gallery media_type, digital shot_type, showreel platform, software
     * proficiency) is a NOT NULL enum with a default, so an explicit null used to
     * reach the INSERT and 500. Omitting the key lets a new item take the column
     * default and leaves an existing item's value unchanged on edit.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function dropBlankSelects(array $data, array $config): array
    {
        foreach ($config['fields'] as [$name, $kind]) {
            if ($kind === 'select' && array_key_exists($name, $data) && blank($data[$name])) {
                unset($data[$name]);
            }
        }

        return $data;
    }

    /**
     * Clean the HTML of rich translatable fields (summary/body) with the shared
     * allowlist sanitizer — the rich-text editor submits markup, so it is
     * sanitized on the way in (and re-sanitized on render, defence in depth).
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function sanitizeRich(array $data, array $config): array
    {
        foreach ($config['fields'] as $f) {
            if (($f[3] ?? false) !== true || ! is_array($data[$f[0]] ?? null)) {
                continue;
            }

            foreach (['en', 'ar'] as $loc) {
                if (isset($data[$f[0]][$loc]) && $data[$f[0]][$loc] !== '') {
                    $data[$f[0]][$loc] = BriefSanitizer::clean((string) $data[$f[0]][$loc]);
                }
            }
        }

        return $data;
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
     * One content item for the editor list: id, position, display label,
     * thumbnail, and every form field's stored value (to prefill Edit).
     *
     * @return array<string, mixed>
     */
    private function present(Model $item, array $config): array
    {
        $labelField = $config['labelField'];
        $translatable = collect($config['fields'])->filter(fn ($f) => $f[1] === 'translatable')->pluck(0)->all();

        $label = in_array($labelField, $translatable, true)
            ? ($item->getTranslation($labelField, app()->getLocale()) ?: '')
            : (string) ($item->getAttribute($labelField) ?? '');

        // Every form field's stored value, so the editor can open an item
        // prefilled (translatable fields carry all locales).
        $values = [];
        foreach ($config['fields'] as [$name, $kind]) {
            $raw = $kind === 'translatable' ? $item->getTranslations($name) : $item->getAttribute($name);
            $values[$name] = $raw instanceof \DateTimeInterface ? $raw->format('Y-m-d') : $raw;
        }

        return [
            'id' => $item->id,
            'position' => (int) $item->position,
            'label' => $label,
            'thumb' => $config['thumb'] ? $item->{$config['thumb']} : null,
            'values' => $values,
        ];
    }
}
