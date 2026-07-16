<?php

namespace Database\Seeders;

use App\Models\ContractFlow;
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
        ['key' => 'brief', 'name' => 'Project brief', 'actor' => 'brand', 'step_type' => 'form', 'is_required' => true, 'is_skippable' => false, 'settings' => ['fields' => ['scope', 'dates', 'budget']], 'instructions' => 'Describe the project, dates and budget.'],
        ['key' => 'quote', 'name' => 'Talent quote', 'actor' => 'talent', 'step_type' => 'form', 'is_required' => true, 'is_skippable' => false, 'settings' => ['fields' => ['amount', 'note'], 'amount_field' => 'amount'], 'instructions' => 'Send your quote for the brief.'],
        ['key' => 'agreement', 'name' => 'Approve quote', 'actor' => 'brand', 'step_type' => 'approval', 'is_required' => true, 'is_skippable' => false, 'settings' => [], 'instructions' => 'Approve or reject the quote.'],
        // The deposit is mandatory — it locks the booking, so it can never be skipped.
        ['key' => 'payment', 'name' => 'Deposit', 'actor' => 'brand', 'step_type' => 'payment', 'is_required' => true, 'is_skippable' => false, 'settings' => ['percentage' => 50, 'confirmation' => 'manual'], 'instructions' => 'Pay the deposit to lock the booking.'],
        ['key' => 'delivery', 'name' => 'Deliver work', 'actor' => 'talent', 'step_type' => 'upload', 'is_required' => true, 'is_skippable' => false, 'settings' => ['collection' => 'delivery'], 'instructions' => 'Upload the final deliverables.'],
        ['key' => 'signoff', 'name' => 'Approve delivery', 'actor' => 'brand', 'step_type' => 'approval', 'is_required' => true, 'is_skippable' => false, 'settings' => [], 'instructions' => 'Approve the delivery to complete the contract.'],
        ['key' => 'complete', 'name' => 'Contract complete', 'actor' => 'system', 'step_type' => 'info', 'is_required' => true, 'is_skippable' => false, 'settings' => [], 'instructions' => null],
    ];

    public function run(): void
    {
        $flow = ContractFlow::query()->updateOrCreate(
            ['slug' => 'standard-booking'],
            ['name' => 'Standard Booking', 'description' => 'The default brand ↔ talent booking flow.', 'applies_to' => null, 'is_active' => true, 'is_default' => true, 'status' => 'active'],
        );

        $flow->steps()->delete();

        foreach (self::STANDARD_STEPS as $position => $step) {
            $flow->steps()->create($step + ['position' => $position]);
        }
    }
}
