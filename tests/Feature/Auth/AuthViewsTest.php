<?php

// Render assertions for the premium auth pages. The role-aware login control
// survives the redesign exactly as specified (Talent | Brand only — staff use
// /admin/login; ?role=brand pre-selects Brand per ADR-P; absent role → talent).

it('renders the login page with the role control defaulting to talent', function () {
    $html = $this->get('/login')->assertOk()->getContent();

    // The segmented control is native radios still posting `role` — and the
    // admin role is NOT offered here (staff use /admin/login).
    expect($html)->toContain('name="role"');
    foreach (['talent', 'brand'] as $role) {
        expect($html)->toContain('value="'.$role.'"');
    }
    expect($html)->not->toContain('value="admin"');
    // Absent ?role → talent is the checked segment.
    expect($html)->toMatch('/value="talent"\s+checked/');
});

it('pre-selects Brand on ?role=brand (ADR-P)', function () {
    $html = $this->get('/login?role=brand')->assertOk()->getContent();

    expect($html)->toMatch('/value="brand"\s+checked/');
    expect($html)->not->toMatch('/value="talent"\s+checked/');
});

it('renders the show-password toggle on every password form', function () {
    foreach (['/login', '/register'] as $url) {
        $html = $this->get($url)->assertOk()->getContent();
        expect($html)->toContain(__('Show password'));
        expect($html)->toContain("show ? 'text' : 'password'");
    }
});

it('renders login validation errors in the restyled markup', function () {
    $response = $this->from('/login')->post('/login', [
        'role' => 'admin', 'email' => 'nobody@example.com', 'password' => 'wrong',
    ]);

    $html = $this->get('/login')->assertOk()->getContent();
    expect($html)->toContain('role="alert"');
});

it('renders the register, forgot-password and login pages on the Fama layout', function () {
    foreach (['/login', '/register', '/forgot-password'] as $url) {
        $html = $this->get($url)->assertOk()->getContent();
        // The Fama wordmark + token body treatment (not stock Breeze grays).
        expect($html)->toContain('Fama<span class="text-accent">.</span>');
        expect($html)->toContain('bg-bg text-ink');
        expect($html)->not->toContain('bg-gray-100');
    }
});
