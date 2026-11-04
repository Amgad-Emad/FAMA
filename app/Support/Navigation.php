<?php

namespace App\Support;

/**
 * The canonical navigation link set for a signed-in talent — defined ONCE so
 * every top bar (the talent dashboard topbar and the public topbar) shows the
 * same links in the same order. Items carry an outline-icon path (used by the
 * pill nav; the public bar renders labels only) and an optional `feature` key
 * that drops the item when its release flag is off (V1).
 */
final class Navigation
{
    /**
     * Ordered, feature-filtered talent nav items:
     * (No Dashboard — folded into Profile structure, 2026-10-12.) Feed · Discover · Activity · [marketplace] · Profile · Profile content · Projects.
     *
     * Each item carries a one-line `description` — the dashboard renders the
     * same set as quick-link cards, so a new nav item shows up there too.
     *
     * @return list<array{route: string, label: string, description: string, icon: string, params?: array<string, string>, feature?: string}>
     */
    public static function talentItems(): array
    {
        $items = [
            ['route' => 'feed', 'label' => __('Feed'), 'description' => __('Share your work and follow the people you work with'), 'icon' => 'M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h6v6h-6v-6z'],
            ['route' => 'discover', 'label' => __('Discover'), 'feature' => 'discover', 'description' => __('Find talents across every skill'), 'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
            ['route' => 'talent.feed.activity', 'label' => __('Activity'), 'description' => __('Likes, comments, mentions and new followers'), 'icon' => 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z'],
            ['route' => 'brands.discover', 'label' => __('Brands'), 'feature' => 'brands', 'description' => __('Browse the brands on Fama'), 'icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm4 4h8m-8 4h8m-8 4h5'],
            ['route' => 'projects.browse', 'label' => __('Opportunities'), 'feature' => 'brands', 'description' => __('Open projects looking for talent'), 'icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z'],
            ['route' => 'talent.contracts', 'label' => __('Contracts'), 'feature' => 'contracts', 'description' => __('Your active bookings'), 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
            ['route' => 'talent.profile.edit', 'label' => __('Edit profile'), 'description' => __('Identity, skills, publishing and the sections of your profile'), 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
            ['route' => 'talent.content', 'label' => __('Profile content'), 'description' => __('Galleries, looks and reviews — the content inside each block'), 'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 6h16a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2z'],
            ['route' => 'talent.projects', 'label' => __('Projects'), 'description' => __('Your selected work — each project tagged with the skills behind it'), 'icon' => 'M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2M3 7h18v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7zm0 5h18'],
            ['route' => 'talent.credits', 'label' => __('Credits'), 'description' => __('Work other people credited you on — confirm it to add it to your profile'), 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
            // Reviews are NOT a nav item: they render as a block panel inside
            // Profile content (the standalone /talent/reviews page still exists
            // and still serves the list's data endpoint).
        ];

        return array_values(array_filter($items, fn ($i) => empty($i['feature']) || Features::enabled($i['feature'])));
    }
}
