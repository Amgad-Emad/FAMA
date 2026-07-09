<?php

namespace App\Support\Talent;

use App\Models\BrandCollab;
use App\Models\Digital;
use App\Models\Equipment;
use App\Models\LookType;
use App\Models\PortfolioItem;
use App\Models\Project;
use App\Models\Showreel;
use App\Models\SoftwareStack;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * Single source of truth for the talent's repeatable "content" child tables
 * (gallery / looks / digitals / showreel / equipment / projects=case-studies /
 * software / brand collabs). One registry drives the web dashboard editors
 * (BlockContentController) and the mobile API (Api\V1\Talent\ContentController):
 * the Eloquent model + talent relation, the media collection (if any), the
 * per-field kinds (for validation) and how a row serialises.
 *
 * Keeping the definition here means adding a field or a content type is a
 * one-place change that both surfaces inherit.
 */
final class BlockContentRegistry
{
    /**
     * The full registry, keyed by content type.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
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
            'projects' => ['model' => Project::class, 'relation' => 'projects', 'label' => 'Projects', 'labelField' => 'title', 'media' => 'cover', 'thumb' => 'cover_image_url', 'fields' => [
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
     * Resolve one type's config or 404 for an unknown type.
     *
     * @return array<string, mixed>
     */
    public static function config(string $type): array
    {
        return self::all()[$type] ?? abort(404, 'Unknown content type.');
    }

    /**
     * The list of content types (type + label) for menus.
     *
     * @return list<array{type: string, label: string}>
     */
    public static function types(): array
    {
        return collect(self::all())
            ->map(fn (array $c, string $k) => ['type' => $k, 'label' => $c['label']])
            ->values()
            ->all();
    }

    /**
     * Validation rules derived from the type's field kinds.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function rules(array $config): array
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
     * The compact list-card shape (id / position / label / thumb) used by the
     * web dashboard, whose editor then opens the full row.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function present(Model $item, array $config): array
    {
        $labelField = $config['labelField'];
        $translatable = self::translatableFields($config);

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

    /**
     * The full row shape for the API — every registry field (translatables as
     * per-locale maps) plus the media conversion URL when the type carries media.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function serialize(Model $item, array $config): array
    {
        $translatable = self::translatableFields($config);

        $out = ['id' => $item->id, 'position' => (int) $item->position];

        foreach ($config['fields'] as [$name, $kind, $options]) {
            $out[$name] = in_array($name, $translatable, true)
                ? $item->getTranslations($name)
                : $item->getAttribute($name);
        }

        if ($config['thumb'] !== null) {
            $out['media_url'] = $item->{$config['thumb']};
        }

        return $out;
    }

    /**
     * The names of the translatable fields for a type.
     *
     * @param  array<string, mixed>  $config
     * @return list<string>
     */
    private static function translatableFields(array $config): array
    {
        return collect($config['fields'])
            ->filter(fn (array $f): bool => $f[1] === 'translatable')
            ->pluck(0)
            ->all();
    }
}
