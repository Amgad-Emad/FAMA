<?php

namespace App\Support;

use App\Models\BrandCollab;
use App\Models\Digital;
use App\Models\Equipment;
use App\Models\LookType;
use App\Models\PortfolioItem;
use App\Models\Project;
use App\Models\Showreel;
use App\Models\SoftwareStack;

/**
 * The catalog of talent content types that have an EDITOR — the child content
 * tables behind the "table" block types (schema-master), each described once:
 * its model + relation, its display label, the label field used in listings,
 * its media collection + thumbnail accessor, and its form fields.
 *
 * Extracted from BlockContentController because two surfaces read it now: that
 * controller (which serves every type through one registry-driven CRUD) and the
 * profile editor (which only offers "Edit content" for a block whose type is
 * actually editable — `reviews` and `comp_card` are table-backed but have no
 * editor here).
 *
 * A field is [name, kind, options, rich?]; `skill_scoped` marks content that
 * carries a talent_type_id (projects — ADR-Q).
 */
final class ContentRegistry
{
    /**
     * Every editable content type, keyed by the block-type key it belongs to.
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
            'projects' => ['model' => Project::class, 'relation' => 'projects', 'label' => 'Projects', 'labelField' => 'title', 'media' => 'cover', 'thumb' => 'cover_image_url', 'skill_scoped' => true, 'fields' => [
                ['title', 'translatable', ['required']],
                ['client_name', 'text', []],
                ['summary', 'translatable', [], true],
                ['body', 'translatable', [], true],
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
     * The block-type keys that have an editor (used to gate "Edit content").
     *
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function has(string $type): bool
    {
        return isset(self::all()[$type]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(string $type): ?array
    {
        return self::all()[$type] ?? null;
    }
}
