<?php

namespace App\Models;

use Database\Factories\DealEnquiryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Deal enquiry (schema-master §3) — the pre-auth Contact capture. Lands here
 * before any brand account exists; converts into a `deals` row once the visitor
 * authenticates as a brand (ConvertEnquiryToDeal sets `converted_deal_id`).
 */
class DealEnquiry extends Model
{
    /** @use HasFactory<DealEnquiryFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'talent_id', 'contact_name', 'contact_email',
        'contact_company', 'brief', 'status', 'converted_deal_id',
    ];

    /**
     * @return BelongsTo<Talent, $this>
     */
    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }

    /**
     * @return BelongsTo<Deal, $this>
     */
    public function convertedDeal(): BelongsTo
    {
        return $this->belongsTo(Deal::class, 'converted_deal_id');
    }
}
