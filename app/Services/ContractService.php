<?php

namespace App\Services;

use App\Actions\Contracting\AdvanceContract;
use App\Actions\Contracting\ConvertEnquiryToContract;
use App\Actions\Contracting\InitiateContract;
use App\Actions\Contracting\RejectStep;
use App\Contracting\ContractProgression;
use App\Models\Brand;
use App\Models\BrandProject;
use App\Models\Contract;
use App\Models\ContractEnquiry;
use App\Models\ContractFlow;
use App\Models\ContractMessage;
use App\Models\Talent;
use App\States\ContractMessage\Read;
use App\States\ContractStep\Skipped;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Contract engine orchestrator (talent-spec "contract loop"; pattern map → Service +
 * Actions + Strategy). The single entry point web/API controllers call into.
 * Every mutation runs inside a transaction with failure logging to the `contracts`
 * channel; the discrete operations live in the Action classes and the shared
 * ContractProgression engine.
 */
class ContractService extends Service
{
    protected string $logChannel = 'contracts';

    public function __construct(
        private readonly InitiateContract $initiate,
        private readonly AdvanceContract $advance,
        private readonly RejectStep $reject,
        private readonly ConvertEnquiryToContract $convert,
        private readonly ContractProgression $progression,
    ) {}

    /**
     * Start a contract from a flow (brand/talent initiated).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function initiate(array $attributes, ContractFlow $flow): Contract
    {
        return $this->runInTransaction(
            fn () => ($this->initiate)($attributes, $flow),
            ['flow_id' => $flow->id, 'talent_id' => $attributes['talent_id'] ?? null],
        );
    }

    /**
     * Complete the current step and advance.
     *
     * @param  array<string, mixed>  $input
     */
    public function advance(Contract $contract, array $input, string $role, ?Model $actor = null): Contract
    {
        return $this->runInTransaction(
            fn () => ($this->advance)($contract, $input, $role, $actor),
            ['contract_id' => $contract->id, 'role' => $role],
        );
    }

    /**
     * Reject the current step and loop the contract back.
     */
    public function reject(Contract $contract, string $role, ?string $reason = null, ?Model $actor = null): Contract
    {
        return $this->runInTransaction(
            fn () => ($this->reject)($contract, $role, $reason, $actor),
            ['contract_id' => $contract->id, 'role' => $role],
        );
    }

    /**
     * Skip the current step (only if it is skippable) and advance.
     */
    public function skip(Contract $contract, string $role, ?Model $actor = null): Contract
    {
        return $this->runInTransaction(function () use ($contract, $role, $actor) {
            $step = $contract->currentStep;

            if ($step === null || ! $step->status->isCurrent()) {
                throw new InvalidArgumentException('This contract has no step awaiting action.');
            }

            if (! $step->is_skippable) {
                throw new InvalidArgumentException('This step cannot be skipped.');
            }

            if (! ($step->actor === $role || $step->actor === 'both' || $role === 'admin')) {
                throw new InvalidArgumentException('It is not your turn on this contract.');
            }

            if ($actor !== null) {
                $step->completedBy()->associate($actor);
                $step->completed_at = now();
                $step->save();
            }

            $step->status->transitionTo(Skipped::class);
            $this->progression->postSystemEvent($contract, $step, ucfirst($role).' skipped '.$step->name.'.',
                ['key' => 'skipped', 'params' => ['actor' => $role, 'step_key' => $step->key, 'step_name' => $step->name]]);
            $this->progression->activateNext($contract);

            return $contract->refresh();
        }, ['contract_id' => $contract->id, 'role' => $role]);
    }

    /**
     * Convert a pre-auth enquiry into a contract.
     */
    public function convertEnquiry(ContractEnquiry $enquiry, Brand $brand, ContractFlow $flow): Contract
    {
        return $this->runInTransaction(
            fn () => ($this->convert)($enquiry, $brand, $flow),
            ['enquiry_id' => $enquiry->id, 'brand_id' => $brand->getKey()],
        );
    }

    /**
     * Post a free-text chat message to the contract thread.
     */
    public function postMessage(Contract $contract, string $role, Model $sender, string $body): ContractMessage
    {
        return $this->runInTransaction(fn () => $contract->messages()->create([
            'sender_type' => $sender->getMorphClass(),
            'sender_id' => $sender->getKey(),
            'sender_role' => $role,
            'type' => 'message',
            'body' => $body,
            'status' => 'sent',
        ]), ['contract_id' => $contract->id, 'role' => $role]);
    }

    /**
     * A talent applies to a brand project: reuse (or open) the talent↔brand contract
     * scoped to this project, then post the application brief (sanitized rich HTML)
     * as a message with any uploaded file attachments. Returns the contract.
     *
     * @param  array<int, \Illuminate\Http\UploadedFile>  $files
     */
    public function applyToProject(BrandProject $project, Talent $talent, string $safeHtml, array $files, ContractFlow $flow): Contract
    {
        return $this->runInTransaction(function () use ($project, $talent, $safeHtml, $files, $flow): Contract {
            $contract = Contract::where('brand_id', $project->brand_id)
                ->where('talent_id', $talent->getKey())
                ->where('brand_project_id', $project->getKey())
                ->latest()
                ->first();

            if ($contract === null) {
                // ($this->initiate) is the action — avoids a nested runInTransaction.
                $contract = ($this->initiate)([
                    'brand_id' => $project->brand_id,
                    'talent_id' => $talent->getKey(),
                    'title' => __('Application — :title', ['title' => $project->title]),
                    'initiated_by' => 'talent',
                ], $flow);

                // brand_project_id is force-filled (not mass-assignable).
                $contract->forceFill(['brand_project_id' => $project->getKey()])->save();
            }

            $message = $contract->messages()->create([
                'sender_type' => $talent->getMorphClass(),
                'sender_id' => $talent->getKey(),
                'sender_role' => 'talent',
                'type' => 'message',
                'body' => $safeHtml,
                'is_rich' => true,
                'status' => 'sent',
            ]);

            foreach ($files as $file) {
                $message->addMedia($file)->toMediaCollection('attachments');
            }

            return $contract;
        }, ['brand_project_id' => $project->getKey(), 'talent_id' => $talent->getKey()]);
    }

    /**
     * Mark the other side's unread chat messages as read. System events are
     * immutable and never marked read.
     */
    public function markThreadRead(Contract $contract, string $role): void
    {
        $contract->messages()
            ->where('type', 'message')
            ->where('status', 'sent')
            ->where('sender_role', '!=', $role)
            ->get()
            ->each(function (ContractMessage $message): void {
                $message->status->transitionTo(Read::class);
                $message->forceFill(['read_at' => now()])->save();
            });
    }
}
