<?php

namespace App\Http\Controllers;

use App\Services\LlmsTxtService;
use App\Services\SitemapService;
use Illuminate\Http\Response;

/**
 * The crawler files (ADR-AO): /robots.txt, /sitemap.xml (+ its files) and
 * /llms.txt. Thin — the services build and cache the bodies. Registered at the
 * site root, outside the locale group, so the addresses never change.
 */
class SeoController extends Controller
{
    public function __construct(
        private readonly SitemapService $sitemaps,
        private readonly LlmsTxtService $llms,
    ) {}

    /**
     * robots.txt — where crawlers may go, and where the sitemap is.
     */
    public function robots(): Response
    {
        return response($this->llms->robots(), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * The sitemap index.
     */
    public function sitemap(): Response
    {
        return response($this->sitemaps->index(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * One sitemap file (pages / profiles-{n} / projects-{n}); unknown → 404.
     */
    public function sitemapFile(string $file): Response
    {
        $xml = $this->sitemaps->file($file);
        abort_if($xml === null, 404);

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * llms.txt — the site explained for AI assistants (Markdown).
     */
    public function llms(): Response
    {
        return response($this->llms->llms(), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
