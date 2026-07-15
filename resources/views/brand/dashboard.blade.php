<x-brand-layout :title="__('Dashboard')">
    <div class="space-y-8">
        {{-- Status banner --}}
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-line bg-surface p-6">
            <div>
                <h2 class="font-display text-2xl">{{ $brand->name }}</h2>
                <p class="mt-1 text-sm text-muted">
                    @if ($stats['is_published'])
                        <span class="inline-flex items-center gap-1 text-ok">● {{ __('Published') }}</span>
                    @else
                        <span class="inline-flex items-center gap-1 text-muted">○ {{ __('Not published') }}</span>
                    @endif
                    @if ($stats['is_verified'])
                        <span class="ms-2 rounded-pill bg-accent-weak px-2 py-0.5 text-xs text-accent-ink">{{ __('Verified') }}</span>
                    @endif
                </p>
            </div>
            @unless ($stats['is_published'])
                <a href="{{ route('brand.account') }}" class="rounded-pill bg-accent px-4 py-2 text-sm text-on-primary hover:opacity-90">{{ __('Publish profile') }}</a>
            @endunless
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            @foreach ([
                ['label' => __('Completed projects'), 'value' => $stats['completed_projects']],
                ['label' => __('Active contracts'), 'value' => $stats['active_contracts']],
                ['label' => __('Projects'), 'value' => $stats['projects']],
                ['label' => __('Status'), 'value' => __(ucfirst($stats['status']))],
            ] as $card)
                <div class="rounded-xl border border-line bg-surface p-4">
                    <div class="font-display text-2xl text-ink">{{ $card['value'] }}</div>
                    <div class="mt-1 text-xs text-muted">{{ $card['label'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- Discovery entry --}}
        <a href="{{ route('discover') }}" class="flex items-center justify-between rounded-xl border border-line bg-accent-weak p-6 hover:opacity-90">
            <div>
                <h3 class="font-display text-lg text-accent-ink">{{ __('Discover talent') }}</h3>
                <p class="text-sm text-accent-ink/80">{{ __('A feed matched to your creative needs.') }}</p>
            </div>
            <svg class="h-6 w-6 text-accent-ink rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
        </a>

        {{-- Active contracts --}}
        <section>
            <div class="mb-3 flex items-center justify-between">
                <h3 class="font-display text-lg">{{ __('Active contracts') }}</h3>
                <a href="{{ route('brand.contracts') }}" class="text-xs text-muted hover:text-ink">{{ __('View all') }}</a>
            </div>
            @forelse ($activeContracts as $contract)
                <a href="{{ route('brand.contracts.show', $contract) }}" class="mb-2 flex items-center justify-between rounded-lg border border-line bg-surface px-4 py-3 hover:border-line-strong">
                    <div>
                        <div class="text-sm font-medium">{{ $contract->title }}</div>
                        <div class="text-xs text-muted">{{ $contract->talent?->display_name }} · {{ $contract->reference }}</div>
                    </div>
                    @if ((string) $contract->status === 'awaiting_brand')
                        <span class="rounded-pill bg-accent px-2 py-0.5 text-xs text-on-primary">{{ __('Your turn') }}</span>
                    @else
                        <span class="rounded-pill bg-elevated px-2 py-0.5 text-xs text-muted">{{ __(ucfirst(str_replace('_',' ', (string) $contract->status))) }}</span>
                    @endif
                </a>
            @empty
                <p class="rounded-lg border border-dashed border-line px-4 py-6 text-center text-sm text-muted">{{ __('No active contracts yet.') }}</p>
            @endforelse
        </section>

        {{-- Recent projects --}}
        <section>
            <div class="mb-3 flex items-center justify-between">
                <h3 class="font-display text-lg">{{ __('Recent projects') }}</h3>
                <a href="{{ route('brand.projects') }}" class="text-xs text-muted hover:text-ink">{{ __('Manage') }}</a>
            </div>
            @forelse ($recentProjects as $campaign)
                <a href="{{ route('brand.projects.show', $campaign) }}" class="mb-2 flex items-center justify-between rounded-lg border border-line bg-surface px-4 py-3 hover:border-line-strong">
                    <span class="text-sm font-medium">{{ $campaign->title }}</span>
                    <span class="rounded-pill bg-elevated px-2 py-0.5 text-xs text-muted">{{ __(ucfirst(str_replace('_',' ', $campaign->status->getValue()))) }}</span>
                </a>
            @empty
                <p class="rounded-lg border border-dashed border-line px-4 py-6 text-center text-sm text-muted">{{ __('No projects yet.') }}</p>
            @endforelse
        </section>
    </div>
</x-brand-layout>
