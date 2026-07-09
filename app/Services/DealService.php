<?php

namespace App\Services;

use App\Actions\Deals\AdvanceDeal;
use App\Actions\Deals\ConvertEnquiryToDeal;
use App\Actions\Deals\InitiateDeal;
use App\Actions\Deals\RejectStep;
use App\Deals\DealProgression;
use App\Models\Brand;
use App\Models\Deal;
use App\Models\DealEnquiry;
use App\Models\DealFlow;
use App\Models\DealMessage;
use App\Notifications\DealTurnChanged;
use App\Notifications\NewDealMessage;
use App\States\DealMessage\Read;
use App\States\DealStep\Skipped;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Deal engine orchestrator (talent-spec "deal loop"; pattern map → Service +
 * Actions + Strategy). The single entry point web/API controllers call into.
 * Every mutation runs inside a transaction with failure logging to the `deals`
 * channel; the discrete operations live in the Action classes and the shared
 * DealProgression engine.
 */
class DealService extends Service
{
    protected string $logChannel = 'deals';

    public function __construct(
        private readonly InitiateDeal $initiate,
        private readonly AdvanceDeal $advance,
        private readonly RejectStep $reject,
        private readonly ConvertEnquiryToDeal $convert,
        private readonly DealProgression $progression,
    ) {}

    /**
     * Start a deal from a flow (brand/talent initiated).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function initiate(array $attributes, DealFlow $flow): Deal
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
    public function advance(Deal $deal, array $input, string $role, ?Model $actor = null): Deal
    {
        $deal = $this->runInTransaction(
            fn () => ($this->advance)($deal, $input, $role, $actor),
            ['deal_id' => $deal->id, 'role' => $role],
        );

        $this->notifyTurnChange($deal);

        return $deal;
    }

    /**
     * Reject the current step and loop the deal back.
     */
    public function reject(Deal $deal, string $role, ?string $reason = null, ?Model $actor = null): Deal
    {
        $deal = $this->runInTransaction(
            fn () => ($this->reject)($deal, $role, $reason, $actor),
            ['deal_id' => $deal->id, 'role' => $role],
        );

        $this->notifyTurnChange($deal);

        return $deal;
    }

    /**
     * Skip the current step (only if it is skippable) and advance.
     */
    public function skip(Deal $deal, string $role, ?Model $actor = null): Deal
    {
        $deal = $this->runInTransaction(function () use ($deal, $role, $actor) {
            $step = $deal->currentStep;

            if ($step === null || ! $step->status->isCurrent()) {
                throw new InvalidArgumentException('This deal has no step awaiting action.');
            }

            if (! $step->is_skippable) {
                throw new InvalidArgumentException('This step cannot be skipped.');
            }

            if (! ($step->actor === $role || $step->actor === 'both' || $role === 'admin')) {
                throw new InvalidArgumentException('It is not your turn on this deal.');
            }

            if ($actor !== null) {
                $step->completedBy()->associate($actor);
                $step->completed_at = now();
                $step->save();
            }

            $step->status->transitionTo(Skipped::class);
            $this->progression->postSystemEvent($deal, $step, ucfirst($role).' skipped '.$step->name.'.');
            $this->progression->activateNext($deal);

            return $deal->refresh();
        }, ['deal_id' => $deal->id, 'role' => $role]);

        $this->notifyTurnChange($deal);

        return $deal;
    }

    /**
     * Convert a pre-auth enquiry into a deal.
     */
    public function convertEnquiry(DealEnquiry $enquiry, Brand $brand, DealFlow $flow): Deal
    {
        return $this->runInTransaction(
            fn () => ($this->convert)($enquiry, $brand, $flow),
            ['enquiry_id' => $enquiry->id, 'brand_id' => $brand->getKey()],
        );
    }

    /**
     * Post a free-text chat message to the deal thread.
     */
    public function postMessage(Deal $deal, string $role, Model $sender, string $body): DealMessage
    {
        $message = $this->runInTransaction(fn () => $deal->messages()->create([
            'sender_type' => $sender->getMorphClass(),
            'sender_id' => $sender->getKey(),
            'sender_role' => $role,
            'type' => 'message',
            'body' => $body,
            'status' => 'sent',
        ]), ['deal_id' => $deal->id, 'role' => $role]);

        $this->notifyNewMessage($deal, $role, $body);

        return $message;
    }

    /**
     * Mark the other side's unread chat messages as read. System events are
     * immutable and never marked read.
     */
    public function markThreadRead(Deal $deal, string $role): void
    {
        $deal->messages()
            ->where('type', 'message')
            ->where('status', 'sent')
            ->where('sender_role', '!=', $role)
            ->get()
            ->each(function (DealMessage $message): void {
                $message->status->transitionTo(Read::class);
                $message->forceFill(['read_at' => now()])->save();
            });
    }

    /**
     * After a step advances/rejects/skips, notify the party whose turn it now is
     * (database notification → the mobile app's notifications feed). System/auto
     * steps and completed deals notify nobody. Best-effort: a `both` step points
     * at the brand (either party may still act — see DealProgression).
     */
    private function notifyTurnChange(Deal $deal): void
    {
        $deal->loadMissing('currentStep', 'talent', 'brand');
        $actor = $deal->currentStep?->actor;

        $recipient = match ($actor) {
            'talent' => $deal->talent,
            'brand', 'both' => $deal->brand,
            default => null,
        };

        $recipient?->notify(new DealTurnChanged($deal, $actor === 'talent' ? 'talent' : 'brand'));
    }

    /**
     * Notify the counterparty (or both parties for an admin note) that a new chat
     * message landed on the deal thread.
     */
    private function notifyNewMessage(Deal $deal, string $senderRole, string $body): void
    {
        $deal->loadMissing('talent', 'brand');

        $recipients = match ($senderRole) {
            'talent' => [$deal->brand],
            'brand' => [$deal->talent],
            default => [$deal->brand, $deal->talent],
        };

        foreach (array_filter($recipients) as $recipient) {
            $recipient->notify(new NewDealMessage($deal, $senderRole, $body));
        }
    }
}
