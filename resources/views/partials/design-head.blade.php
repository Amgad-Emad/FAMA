{{-- Fama design-system fonts + no-flash theme init (data-theme, persisted). --}}
@php
    // Locale prefix for JS: '' on the default (en, hidden in URL), '/ar' otherwise.
    // http.js prefixes Ajax URLs with this so a page opened under /ar returns
    // Arabic content; nav links localize the same way.
    $defaultLocale = \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getDefaultLocale();
    $currentLocale = app()->getLocale();
    $localePrefix = $currentLocale === $defaultLocale ? '' : '/'.$currentLocale;

    // Translated status labels for $statusLabel (JS pills can't call __()).
    $statusLabels = [
        'created' => __('Created'), 'draft' => __('Draft'), 'live' => __('Live'),
        'unpublished' => __('Unpublished'), 'suspended' => __('Suspended'), 'archived' => __('Archived'),
        'registered' => __('Registered'), 'onboarding' => __('Onboarding'), 'complete' => __('Complete'),
        'published' => __('Published'),
        'awaiting_brand' => __('Awaiting brand'), 'awaiting_talent' => __('Awaiting talent'),
        'awaiting_admin' => __('Awaiting admin'), 'completed' => __('Completed'),
        'cancelled' => __('Cancelled'), 'declined' => __('Declined'), 'expired' => __('Expired'),
        'open' => __('Open'), 'in_progress' => __('In progress'), 'active' => __('Active'),
        'pending' => __('Pending'), 'approved' => __('Approved'), 'rejected' => __('Rejected'),
    ];

    // Contract actor + step labels — shared with the server (ContractLabels) so
    // the timeline reads identically in Blade and JS.
    $actorLabels = \App\Support\ContractLabels::actorLabels();
    $stepLabels = \App\Support\ContractLabels::stepLabels();

    // Talent-type category display labels (the model|crew|creative enum + `all`).
    $categoryLabels = ['model' => __('Modeling'), 'crew' => __('Crew'), 'creative' => __('Creative'), 'all' => __('All categories')];

    // Seeded default contract-flow names, keyed by slug; custom flows fall back
    // to their stored (admin-authored) name.
    $flowLabels = [
        'standard-booking' => __('Standard Booking'),
        'quick-shoot' => __('Quick Shoot'),
        'premium-booking' => __('Premium Booking'),
    ];
@endphp
<meta name="locale-prefix" content="{{ $localePrefix }}">

<script>
    window.__famaStatusLabels = {!! json_encode($statusLabels, JSON_UNESCAPED_UNICODE) !!};
    window.__famaActorLabels = {!! json_encode($actorLabels, JSON_UNESCAPED_UNICODE) !!};
    window.__famaStepLabels = {!! json_encode($stepLabels, JSON_UNESCAPED_UNICODE) !!};
    window.__famaCategoryLabels = {!! json_encode($categoryLabels, JSON_UNESCAPED_UNICODE) !!};
    window.__famaFlowLabels = {!! json_encode($flowLabels, JSON_UNESCAPED_UNICODE) !!};
</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400..800&family=Sora:wght@400;500;600;700&family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">

<script>
    (function () {
        var stored = localStorage.getItem('theme');
        var dark = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
    })();
</script>
