{{-- The sitemap index (ADR-AO) — App\Services\SitemapService::index(). Param: $files [{loc, lastmod}]. --}}
{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($files as $file)
    <sitemap><loc>{{ $file['loc'] }}</loc>@if ($file['lastmod'])<lastmod>{{ $file['lastmod'] }}</lastmod>@endif</sitemap>
@endforeach
</sitemapindex>
