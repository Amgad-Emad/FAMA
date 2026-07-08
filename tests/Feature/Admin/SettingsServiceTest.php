<?php

use App\Models\Setting;
use App\Services\SettingsService;

beforeEach(function () {
    $this->settings = app(SettingsService::class);
});

it('sets and reads back a scalar setting', function () {
    $this->settings->set('default_currency', 'USD');

    expect($this->settings->get('default_currency'))->toBe('USD');
    expect(Setting::where('key', 'default_currency')->exists())->toBeTrue();
});

it('returns the provided default for a missing key', function () {
    expect($this->settings->get('missing', 'fallback'))->toBe('fallback');
    expect($this->settings->has('missing'))->toBeFalse();
});

it('stores and reads array values (feature flags)', function () {
    $this->settings->set('feature_flags', ['brand_initiated_deals' => true, 'x' => false]);

    expect($this->settings->featureEnabled('brand_initiated_deals'))->toBeTrue();
    expect($this->settings->featureEnabled('x'))->toBeFalse();
    expect($this->settings->featureEnabled('unknown', true))->toBeTrue(); // falls back to default
});

it('exposes the typed globals with sensible defaults', function () {
    // Defaults before anything is stored.
    expect($this->settings->defaultCurrency())->toBe('EGP');
    expect($this->settings->defaultDealFlowId())->toBeNull();

    $this->settings->setMany(['default_currency' => 'AED', 'default_deal_flow_id' => 7]);

    expect($this->settings->defaultCurrency())->toBe('AED');
    expect($this->settings->defaultDealFlowId())->toBe(7);
});

it('busts the cache on every write', function () {
    $this->settings->set('k', 'v1');
    expect($this->settings->get('k'))->toBe('v1');

    $this->settings->set('k', 'v2');
    expect($this->settings->get('k'))->toBe('v2');
});

it('forgets a setting', function () {
    $this->settings->set('k', 'v');
    $this->settings->forget('k');

    expect($this->settings->has('k'))->toBeFalse();
    expect(Setting::where('key', 'k')->exists())->toBeFalse();
});
