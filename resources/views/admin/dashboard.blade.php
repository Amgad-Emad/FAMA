<x-admin-layout :title="__('Admin dashboard')">
    <div class="space-y-8">
        <p class="text-sm text-muted">{{ __('Platform governance overview.') }}</p>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            @foreach ([
                ['label' => __('Pending reviews'), 'value' => $stats['pending_reviews'], 'to' => 'admin.moderation.index', 'can' => 'moderate-content'],
                ['label' => __('Pending brand reviews'), 'value' => $stats['pending_brand_reviews'], 'to' => 'admin.moderation.index', 'can' => 'moderate-content'],
                ['label' => __('Deals awaiting admin'), 'value' => $stats['awaiting_admin'], 'to' => 'admin.deals', 'can' => 'intervene-deals'],
                ['label' => __('Active deals'), 'value' => $stats['active_deals'], 'to' => 'admin.deals', 'can' => 'intervene-deals'],
                ['label' => __('Deal flows'), 'value' => $stats['flows'], 'to' => 'admin.flows', 'can' => 'manage-flows'],
                ['label' => __('Active flows'), 'value' => $stats['active_flows'], 'to' => 'admin.flows', 'can' => 'manage-flows'],
                ['label' => __('Talents'), 'value' => $stats['talents'], 'to' => null, 'can' => null],
                ['label' => __('Brands'), 'value' => $stats['brands'], 'to' => null, 'can' => null],
            ] as $card)
                @php $link = $card['to'] && (! $card['can'] || $admin->can($card['can'])); @endphp
                @if ($link)
                    <a href="{{ route($card['to']) }}" class="rounded-xl border border-line bg-surface p-4 transition hover:border-line-strong">
                        <div class="font-display text-3xl leading-none text-ink">{{ $card['value'] }}</div>
                        <div class="mt-1 font-mono text-[11px] uppercase tracking-wider text-subtle">{{ $card['label'] }}</div>
                    </a>
                @else
                    <div class="rounded-xl border border-line bg-surface p-4">
                        <div class="font-display text-3xl leading-none text-ink">{{ $card['value'] }}</div>
                        <div class="mt-1 font-mono text-[11px] uppercase tracking-wider text-subtle">{{ $card['label'] }}</div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</x-admin-layout>
