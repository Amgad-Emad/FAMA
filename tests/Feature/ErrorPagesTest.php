<?php

// The app owns branded error pages (403/404/419/429/500/503) on the Fama
// design system. A real 404 must serve ours; every view must carry its code
// and a way out (the feed for guests, the profile for talents).

it('serves the branded 404 for a missing page', function () {
    $response = $this->get('/definitely/not/a/real/page');

    $response->assertNotFound();
    $html = $response->getContent();
    expect($html)->toContain('404');
    expect($html)->toContain(__('Page not found'));
    expect($html)->toContain(e(__("This page isn't here")));
    expect($html)->toContain(url('/'));
    // The fama-ui composition: static header + the big code + one way out.
    expect($html)->toContain('fama-ui/css/ds.css');
    expect($html)->toContain('class="fx-nav"');
    expect($html)->toContain('class="nf"');
    expect($html)->not->toContain('/build/assets/');
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
            ->toContain(__('Go to the feed'))
            ->toContain(url('/'));
    }
});

it('sends a signed-in talent to their profile structure', function () {
    $talent = \App\Models\Talent::factory()->create();

    $html = $this->actingAs($talent, 'talent')->get('/definitely/not/a/real/page')->assertNotFound()->getContent();

    expect($html)->toContain(__('Go to your profile'))
        ->toContain(route('talent.profile.edit'));
});
