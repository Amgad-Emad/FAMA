<?php

namespace App\Services;

use App\Actions\Brand\RecalculateBrandCredibility;
use App\Models\Brand;
use App\Models\BrandCredibility;

/**
 * Orchestrates brand credibility accrual (brand-spec workflow 8). Wraps the
 * recalculation in a transaction with fail-logging to the `brands` channel.
 */
class BrandCredibilityService extends Service
{
    protected string $logChannel = 'brands';

    public function __construct(private readonly RecalculateBrandCredibility $recalculate) {}

    public function recalculate(Brand $brand): BrandCredibility
    {
        return $this->runInTransaction(
            fn () => ($this->recalculate)($brand),
            ['brand_id' => $brand->getKey()],
        );
    }
}
