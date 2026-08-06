<?php

namespace Database\Seeders;

use App\Models\ContractFlow;
use App\Models\ContractTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeds the default "Standard Booking" contract flow — the ordered steps a contract is
 * snapshotted from. A full loop exercising every actor and the core step types:
 * brief (brand) → quote (talent) → agreement (brand) → payment (brand) →
 * delivery (talent) → sign-off (brand) → complete (system, auto).
 */
class ContractFlowSeeder extends Seeder
{
    /**
     * Canonical steps for the standard flow. Shared with ContractFlowFactory so the
     * seeded flow and factory-built flows stay identical.
     *
     * @var list<array<string, mixed>>
     */
    public const array STANDARD_STEPS = [
        ['key' => 'brief', 'name' => 'Project brief', 'actor' => 'brand', 'step_type' => 'form', 'is_required' => true, 'is_skippable' => false, 'settings' => ['fields' => ['scope', 'start_date', 'end_date', 'budget'], 'writes' => ['scope' => 'brief', 'start_date' => 'start_date', 'end_date' => 'end_date']], 'instructions' => 'Describe the project scope, start/finish dates and budget.'],
        ['key' => 'quote', 'name' => 'Talent quote', 'actor' => 'talent', 'step_type' => 'form', 'is_required' => true, 'is_skippable' => false, 'settings' => ['fields' => ['amount', 'pricing_unit', 'note'], 'amount_field' => 'amount', 'writes' => ['pricing_unit' => 'pricing_unit']], 'instructions' => 'Send your quote — amount and how it is priced (per hour / day / item).'],
        ['key' => 'agreement', 'name' => 'Approve quote', 'actor' => 'brand', 'step_type' => 'approval', 'is_required' => true, 'is_skippable' => false, 'settings' => [], 'instructions' => 'Approve or reject the quote.'],
        // The deposit is mandatory — it locks the booking, so it can never be skipped.
        ['key' => 'payment', 'name' => 'Deposit', 'actor' => 'brand', 'step_type' => 'payment', 'is_required' => true, 'is_skippable' => false, 'settings' => ['percentage' => 50, 'confirmation' => 'manual'], 'instructions' => 'Pay the deposit to lock the booking.'],
        ['key' => 'delivery', 'name' => 'Deliver work', 'actor' => 'talent', 'step_type' => 'upload', 'is_required' => true, 'is_skippable' => false, 'settings' => ['collection' => 'delivery'], 'instructions' => 'Upload the final deliverables.'],
        ['key' => 'signoff', 'name' => 'Approve delivery', 'actor' => 'brand', 'step_type' => 'approval', 'is_required' => true, 'is_skippable' => false, 'settings' => [], 'instructions' => 'Approve the delivery to complete the contract.'],
        ['key' => 'complete', 'name' => 'Contract complete', 'actor' => 'system', 'step_type' => 'info', 'is_required' => true, 'is_skippable' => false, 'settings' => [], 'instructions' => null],
    ];

    public function run(): void
    {
        // Link to the default contract template so seeded contracts get a document (ADR-V).
        $templateId = ContractTemplate::query()->where('slug', 'fama-standard')->value('id');

        $flow = ContractFlow::query()->updateOrCreate(
            ['slug' => 'standard-booking'],
            ['name' => 'Standard Booking', 'description' => 'The default brand ↔ talent booking flow.', 'applies_to' => null, 'contract_template_id' => $templateId, 'is_active' => true, 'is_default' => true, 'status' => 'active'],
        );

        $flow->steps()->delete();

        foreach (self::STANDARD_STEPS as $position => $step) {
            $flow->steps()->create($step + ['position' => $position]);
        }
    }
}
