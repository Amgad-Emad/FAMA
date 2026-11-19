@props(['icon' => null])

{{--
    Inline SVG glyph for a skill, keyed by `talent_types.icon` (lucide-<slug>).
    Self-contained (no icon runtime); a sparkle fallback keeps unknown icons from
    rendering blank. The glyphs live in resources/data/skill-icons.json (all 41 skills).
--}}
@php
    // ONE map for every skill glyph (resources/data/skill-icons.json — also imported by dashboard.js).
    static $paths = null;
    $paths ??= json_decode((string) file_get_contents(resource_path('data/skill-icons.json')), true) ?: [];
    $inner = $paths[$icon] ?? $paths['_fallback'] ?? '';
@endphp

<svg {{ $attributes->merge(['class' => 'h-4 w-4']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $inner !!}</svg>
