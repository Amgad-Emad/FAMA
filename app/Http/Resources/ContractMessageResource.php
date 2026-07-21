<?php

namespace App\Http\Resources;

use App\Models\ContractMessage;
use App\Support\ContractLabels;
use Illuminate\Http\Request;

/**
 * @mixin ContractMessage
 */
class ContractMessageResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'sender_role' => $this->sender_role,
            'body' => $this->localizedBody(),
            'is_rich' => (bool) $this->is_rich,
            'is_system' => $this->isSystem(),
            // Uploaded application/message files ([{name, url, size}]).
            'attachments' => $this->file_attachments,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Localize a system-event body from its structured `meta` ({key, params})
     * in the CURRENT locale — so the timeline reads in the viewer's language,
     * not whoever performed the action. Falls back to the stored English body
     * for messages without meta (older rows, non-system messages).
     */
    private function localizedBody(): string
    {
        $meta = $this->meta;
        if (! is_array($meta) || empty($meta['key'])) {
            return (string) $this->body;
        }

        $p = $meta['params'] ?? [];
        $actor = ContractLabels::actor($p['actor'] ?? 'system');
        $step = ContractLabels::step($p['step_key'] ?? null, $p['step_name'] ?? null);
        $reason = $p['reason'] ?? null;

        return match ($meta['key']) {
            'submitted' => __(':actor submitted :step.', compact('actor', 'step')),
            'delivered' => __(':actor delivered the work for :step.', compact('actor', 'step')),
            'approved' => __(':actor approved :step.', compact('actor', 'step')),
            'paid' => __(':actor paid the :pct% deposit.', ['actor' => $actor, 'pct' => (int) ($p['pct'] ?? 0)]),
            'signed' => __(':actor signed :step.', compact('actor', 'step')),
            'scheduled' => __(':actor scheduled :step.', compact('actor', 'step')),
            'sent' => __(':actor sent :step.', compact('actor', 'step')),
            'skipped' => __(':actor skipped :step.', compact('actor', 'step')),
            'rejected' => $reason
                ? __(':actor rejected :step: :reason', compact('actor', 'step', 'reason'))
                : __(':actor rejected :step.', compact('actor', 'step')),
            'completed' => __(':actor completed :step.', compact('actor', 'step')),
            'contract_completed' => __('Contract completed.'),
            default => (string) $this->body,
        };
    }
}
