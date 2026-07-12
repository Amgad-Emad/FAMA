<?php

use App\Models\CompCard;
use App\Models\PortfolioItem;
use App\Models\Talent;
use App\Models\TalentType;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;

it('casts json, booleans, integers and dates', function () {
    $talent = Talent::factory()->create([
        'meta' => ['onboarded' => true],
        'is_published' => true,
        'view_count' => 12,
    ]);

    $fresh = Talent::find($talent->id);

    expect($fresh->meta)->toBe(['onboarded' => true]);
    expect($fresh->is_published)->toBeTrue();
    expect($fresh->view_count)->toBe(12);
    expect($fresh->published_at)->toBeInstanceOf(Carbon::class);
});

it('stores headline and bio as translatable per-locale values', function () {
    $talent = Talent::factory()->create([
        'headline' => ['en' => 'Photographer', 'ar' => 'مصور'],
        'bio' => ['en' => 'Bio here', 'ar' => 'نبذة'],
    ]);

    $fresh = Talent::find($talent->id);

    expect($fresh->getTranslation('headline', 'en'))->toBe('Photographer');
    expect($fresh->getTranslation('headline', 'ar'))->toBe('مصور');

    app()->setLocale('ar');
    expect($fresh->bio)->toBe('نبذة');
    app()->setLocale('en');
});

it('auto-generates a slug when none is supplied', function () {
    $talent = Talent::create([
        'email' => 'noSlug@example.com',
        'password' => 'password',
        'display_name' => 'Nour El Din',
    ]);

    expect($talent->slug)->not->toBeEmpty();
    expect($talent->slug)->toStartWith('nour-el-din-');
});

it('soft deletes', function () {
    $talent = Talent::factory()->create();

    $talent->delete();

    expect(Talent::find($talent->id))->toBeNull();
    expect(Talent::withTrashed()->find($talent->id))->not->toBeNull();
});

it('exposes the talent content relationships', function () {
    $talent = Talent::factory()->create();
    PortfolioItem::factory()->count(2)->for($talent)->create();
    CompCard::factory()->for($talent)->create();

    $fresh = Talent::withCount(['portfolioItems'])->with('compCard')->find($talent->id);

    expect($fresh->portfolio_items_count)->toBe(2);
    expect($fresh->compCard)->toBeInstanceOf(CompCard::class);
});

it('links skills through the pivot ordered by position with a primary', function () {
    $talent = Talent::factory()->create();
    $model = TalentType::factory()->create();
    $photographer = TalentType::factory()->create();

    $talent->talentTypes()->attach([
        $model->id => ['is_primary' => true, 'position' => 0],
        $photographer->id => ['is_primary' => false, 'position' => 1],
    ]);

    $fresh = Talent::with('talentTypes')->find($talent->id);

    expect($fresh->talentTypes)->toHaveCount(2);
    expect($fresh->talentTypes->first()->id)->toBe($model->id);
    expect((int) $fresh->talentTypes->first()->pivot->is_primary)->toBe(1);
});

it('enforces a unique talent/type pair', function () {
    $talent = Talent::factory()->create();
    $type = TalentType::factory()->create();
    $talent->talentTypes()->attach($type->id, ['position' => 0]);

    expect(fn () => $talent->talentTypes()->attach($type->id, ['position' => 1]))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('registers the avatar media collection with a null accessor when empty', function () {
    $talent = Talent::factory()->create();

    $collections = collect($talent->getRegisteredMediaCollections())->pluck('name');

    expect($collections)->toContain('avatar');
    expect($collections)->not->toContain('hero'); // cover/hero removed (ADR-O)
    expect($talent->avatar_url)->toBeNull();
});
