<?php

use App\Models\CompCard;
use App\Models\PortfolioItem;
use App\Models\ProfileBlock;
use App\Models\Showreel;
use App\Models\Talent;
use Illuminate\Database\UniqueConstraintViolationException;

it('portfolio item belongs to a talent and a block and casts json', function () {
    $item = PortfolioItem::factory()->create([
        'credits' => ['photographer' => 'Sara'],
        'tags' => ['editorial'],
    ]);

    $fresh = PortfolioItem::with(['talent', 'block'])->find($item->id);

    expect($fresh->talent)->toBeInstanceOf(Talent::class);
    expect($fresh->credits)->toBe(['photographer' => 'Sara']);
    expect($fresh->tags)->toBe(['editorial']);
});

it('portfolio item can link to a gallery block', function () {
    $talent = Talent::factory()->create();
    $block = ProfileBlock::factory()->for($talent)->create();
    $item = PortfolioItem::factory()->for($talent)->create(['block_id' => $block->id]);

    expect(PortfolioItem::with('block')->find($item->id)->block)->toBeInstanceOf(ProfileBlock::class);
});

it('embed portfolio items expose the external url via the accessor', function () {
    $item = PortfolioItem::factory()->embed()->create();

    expect($item->media_type)->toBe('embed');
    expect($item->media_url)->toBe($item->embed_url);
    expect($item->media_url)->toStartWith('https://');
});

it('uploaded portfolio items have null media accessors until a file is attached', function () {
    $item = PortfolioItem::factory()->create(['media_type' => 'image']);

    expect($item->media_url)->toBeNull();
    expect(collect($item->getRegisteredMediaCollections())->pluck('name'))->toContain('gallery');
});

it('keeps a comp card one-to-one with the talent', function () {
    $talent = Talent::factory()->create();
    CompCard::factory()->for($talent)->create();

    expect(fn () => CompCard::factory()->for($talent)->create())
        ->toThrow(UniqueConstraintViolationException::class);
});

it('keeps showreel video_url external while the thumbnail comes from media', function () {
    $reel = Showreel::factory()->create();

    expect($reel->video_url)->toStartWith('https://');
    expect($reel->thumbnail_url)->toBeNull();
    expect(collect($reel->getRegisteredMediaCollections())->pluck('name'))->toContain('thumbnail');
});
