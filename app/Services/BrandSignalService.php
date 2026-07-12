<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\BrandSignal;
use App\Models\Talent;

/**
 * Append-only behaviour logging (brand-spec workflow 4) — view / save /
 * brief_sent / profile_open. Feeds the discovery preference engine. Write-once;
 * never edited.
 */
class BrandSignalService extends Service
{
    protected string $logChannel = 'brands';

    /**
     * @param  array<string, mixed>|null  $context
     */
    public function record(Brand $brand, string $action, ?Talent $talent = null, ?array $context = null): BrandSignal
    {
        return $this->runInTransaction(
            fn () => $brand->signals()->create([
                'action_type' => $action,
                'talent_id' => $talent?->getKey(),
                'context' => $context,
            ]),
            ['brand_id' => $brand->getKey(), 'action' => $action],
        );
    }

    public function view(Brand $brand, Talent $talent): BrandSignal
    {
        return $this->record($brand, 'view', $talent);
    }

    public function save(Brand $brand, Talent $talent): BrandSignal
    {
        return $this->record($brand, 'save', $talent);
    }

    public function brief(Brand $brand, Talent $talent): BrandSignal
    {
        return $this->record($brand, 'brief_sent', $talent);
    }

    public function profileOpen(Brand $brand, Talent $talent): BrandSignal
    {
        return $this->record($brand, 'profile_open', $talent);
    }
}
