<?php

use App\Models\Talent;
use App\Models\User;
use App\Services\MediaOversightService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
    $this->powerless = User::factory()->create();
});

it('re-runs conversions for a media item and audits it', function () {
    Storage::fake('public');
    $talent = Talent::factory()->create();
    $media = $talent->addMedia(UploadedFile::fake()->image('hero.jpg', 800, 600))->toMediaCollection('hero');

    app(MediaOversightService::class)->retry($this->admin, $media);

    $activity = Activity::inLog('media')->where('description', 'media.conversions_retried')->first();
    expect($activity)->not->toBeNull();
    expect((int) $activity->causer_id)->toBe($this->admin->id);
});

it('denies media retry to an admin without manage-settings', function () {
    Storage::fake('public');
    $talent = Talent::factory()->create();
    $media = $talent->addMedia(UploadedFile::fake()->image('hero.jpg', 400, 400))->toMediaCollection('hero');

    expect(fn () => app(MediaOversightService::class)->retry($this->powerless, $media))
        ->toThrow(AuthorizationException::class);
});
