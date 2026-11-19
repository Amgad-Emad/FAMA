{{--
    A page's search + share metadata (ADR-AO), from an App\Support\Seo: description, canonical, robots,
    Open Graph + Twitter card (so a shared link unfurls with a picture) and JSON-LD structured data.
    Rendered by <x-fama.layout> for every fama-ui page. Param: $seo, $title (the full <title>).
--}}
<meta name="description" content="{{ $seo->getDescription() }}">
<link rel="canonical" href="{{ $seo->getCanonical() }}">
<meta name="robots" content="{{ $seo->isIndexable() ? 'index, follow, max-image-preview:large' : 'noindex, nofollow' }}">
<meta property="og:site_name" content="{{ config('seo.site_name') }}">
<meta property="og:type" content="{{ $seo->getType() }}">
<meta property="og:title" content="{{ $seo->getTitle() ?? $title }}">
<meta property="og:description" content="{{ $seo->getDescription() }}">
<meta property="og:url" content="{{ $seo->getCanonical() }}">
<meta property="og:image" content="{{ $seo->getImage() }}">
<meta property="og:locale" content="{{ str_replace('-', '_', \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getCurrentLocaleRegional() ?: 'en_GB') }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo->getTitle() ?? $title }}">
<meta name="twitter:description" content="{{ $seo->getDescription() }}">
<meta name="twitter:image" content="{{ $seo->getImage() }}">
@foreach ($seo->getJsonLd() as $json)
    <script type="application/ld+json">{!! $json !!}</script>
@endforeach
