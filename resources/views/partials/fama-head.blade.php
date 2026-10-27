{{--
    Runtime bridge for the fama-ui layout: the locale prefix http.js reads and
    the translated label maps the Alpine magics ($statusLabel, $actorLabel, …)
    expect. Same data as partials/design-head minus its fonts + theme script —
    the fama-ui design is light-only and loads its own fonts (ds.css/shell.css).
--}}
@php
    $defaultLocale = \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getDefaultLocale();
    $currentLocale = app()->getLocale();
    $localePrefix = $currentLocale === $defaultLocale ? '' : '/'.$currentLocale;
    $statusLabels = [
        'created' => __('Created'), 'draft' => __('Draft'), 'live' => __('Live'),
        'unpublished' => __('Unpublished'), 'suspended' => __('Suspended'), 'archived' => __('Archived'),
        'published' => __('Published'), 'pending' => __('Pending'), 'approved' => __('Approved'),
        'rejected' => __('Rejected'), 'accepted' => __('Accepted'), 'declined' => __('Declined'),
        'listed' => __('Listed'), 'completed' => __('Completed'), 'cancelled' => __('Cancelled'),
        'open' => __('Open'), 'in_progress' => __('In progress'), 'active' => __('Active'),
    ];
@endphp
<meta name="locale-prefix" content="{{ $localePrefix }}">
<script>
    document.documentElement.setAttribute('data-theme', 'light');
    // Whether the viewer can act (like/save/follow/comment). Buttons check this
    // BEFORE flipping, so a visitor never sees "Following" flash before sign-in.
    window.famaSignedIn = {{ auth('talent')->check() || auth('brand')->check() ? 'true' : 'false' }};
    // A signed-in admin browsing public pages: read-only (admins are never feed
    // actors), so action buttons explain that instead of offering a sign-in.
    window.famaStaff = {{ auth('admin')->check() && ! auth('talent')->check() && ! auth('brand')->check() ? 'true' : 'false' }};
    window.famaStaffNote = @js(__('You’re signed in as Fama staff. Liking, saving, following and commenting are for talent accounts.'));
    window.__famaStatusLabels = {!! json_encode($statusLabels, JSON_UNESCAPED_UNICODE) !!};
    window.__famaActorLabels = {!! json_encode(\App\Support\ContractLabels::actorLabels(), JSON_UNESCAPED_UNICODE) !!};
    window.__famaStepLabels = {!! json_encode(\App\Support\ContractLabels::stepLabels(), JSON_UNESCAPED_UNICODE) !!};
    window.__famaCategoryLabels = {!! json_encode(['model' => __('Modeling'), 'crew' => __('Crew'), 'creative' => __('Creative'), 'all' => __('All categories')], JSON_UNESCAPED_UNICODE) !!};
    window.__famaFlowLabels = {};
</script>
