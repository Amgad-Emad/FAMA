<?php

// The app owns branded error pages (403/404/419/429/500/503) on the Fama
// design system. A real 404 must serve ours; every view must carry its code
// and a way home.

it('serves the branded 404 for a missing page', function () {
    $response = $this->get('/definitely/not/a/real/page');

    $response->assertNotFound();
    $html = $response->getContent();
    expect($html)->toContain('404');
    expect($html)->toContain(__('Page not found'));
    expect($html)->toContain(url('/'));
    expect($html)->toContain('Fama<span class="text-accent">.</span>');
});

it('renders every owned error view with its code and a home link', function () {
    foreach ([
        '403' => 'Access denied',
        '404' => 'Page not found',
        '419' => 'Page expired',
        '429' => 'Too many requests',
        '500' => 'Something went wrong',
        '503' => 'Down for maintenance',
    ] as $code => $title) {
        $html = view("errors.{$code}")->render();
        expect($html)->toContain((string) $code)
            ->toContain(__($title))
            ->toContain(__('Back to home'))
            ->toContain(url('/'));
    }
});
