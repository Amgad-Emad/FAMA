<?php

namespace App\Models;

use Database\Factories\ContractEnquiryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contract enquiry (schema-master §3) — the pre-auth Contact capture. Lands here
 * before any brand account exists; converts into a `contracts` row once the visitor
 * authenticates as a brand (ConvertEnquiryToContract sets `converted_contract_id`).
 */
class ContractEnquiry extends Model
{
    /** @use HasFactory<ContractEnquiryFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'talent_id', 'contact_name', 'contact_email',
        'contact_company', 'brief', 'status', 'converted_contract_id',
    ];

    /**
     * @return BelongsTo<Talent, $this>
     */
    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }

    /**
     * @return BelongsTo<Contract, $this>
     */
    public function convertedContract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'converted_contract_id');
    }
}
