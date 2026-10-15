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
        // The no-JS default stays masked.
        expect($html)->toContain('type="password"');
    }
});

it('renders login validation errors in the restyled markup', function () {
    $response = $this->from('/login')->post('/login', [
        'role' => 'admin', 'email' => 'nobody@example.com', 'password' => 'wrong',
    ]);

    $html = $this->get('/login')->assertOk()->getContent();
    expect($html)->toContain('role="alert"');
});

it('renders the register, forgot-password and login pages on the fama-ui layout', function () {
    foreach (['/login', '/register', '/forgot-password'] as $url) {
        $html = $this->get($url)->assertOk()->getContent();
        // The fama-ui shell: its own stylesheets + the auth sheet, the auth nav
        // (logo + "Browse the feed"), and the prototype's pane — no Tailwind app.css.
        expect($html)->toContain('fama-ui/css/auth.css');
        expect($html)->toContain('class="fx-browse"');
        expect($html)->toContain('class="pane');
        expect($html)->not->toContain('bg-gray-100');
    }

    // Login + register are the split screen with the showcase panel.
    foreach (['/login', '/register'] as $url) {
        expect($this->get($url)->getContent())->toContain('class="au-show"');
    }
});

it('marks the failing field and replaces its help line with the server error', function () {
    $this->from('/register')->post('/register', [
        'account_type' => 'talent', 'name' => '', 'email' => 'not-an-email',
        'password' => 'short', 'password_confirmation' => 'nope',
    ]);

    $html = $this->get('/register')->assertOk()->getContent();
    expect($html)->toContain('class="f bad"');
    expect($html)->toContain('class="help err"');
    expect($html)->toContain('aria-invalid="true"');
});

it('shows the sent state once a reset link is emailed', function () {
    \Illuminate\Support\Facades\Notification::fake();
    // The default password broker resolves the `users` provider.
    $user = \App\Models\User::factory()->create();

    $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email]);

    $html = $this->get('/forgot-password')->assertOk()->getContent();
    expect($html)->toContain(e(__("We've emailed your reset link")));
    expect($html)->toContain(e($user->email));
});

it('tells a guest bounced from a follow what logging in will finish', function () {
    $talent = \App\Models\Talent::factory()->create(['display_name' => 'Nour Studio']);

    $html = $this->withSession(['feed.pending_intent' => [
        'action' => 'follow', 'target_type' => 'talent', 'target_id' => $talent->id, 'payload' => [],
    ]])->get('/login')->assertOk()->getContent();

    expect($html)->toContain('id="next"')
        ->toContain(e(__('Log in to finish following :name.', ['name' => $talent->feedDisplayName()])));
});

it('shows no intent note on a plain visit', function () {
    expect($this->get('/login')->getContent())->not->toContain('id="next"');
});
