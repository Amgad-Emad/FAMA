<x-talent-layout :title="__('Dashboard')">
    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
            <x-ui.eyebrow>{{ __('Welcome back') }}</x-ui.eyebrow>
            <h2 class="mt-1 font-display text-3xl text-ink">{{ $talent->display_name ?: $talent->email }}</h2>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.card>
            <div class="font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Profile status') }}</div>
            <div class="mt-2 flex items-center gap-2">
                <span class="h-2.5 w-2.5 rounded-pill {{ $stats['is_published'] ? 'bg-success' : 'bg-warn' }}"></span>
                <span class="font-display text-xl text-ink">{{ $stats['is_published'] ? __('Live') : __(ucfirst($stats['status'])) }}</span>
            </div>
            <a href="{{ route('talent.profile.edit') }}" class="mt-3 inline-block text-xs text-accent-ink underline">
                {{ $stats['is_published'] ? __('Manage publishing') : __('Publish your profile') }}
            </a>
        </x-ui.card>

        <x-ui.stat :label="__('Profile views')" :value="number_format($stats['view_count'])" />

        <x-ui.card>
            <div class="font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Pending reviews') }}</div>
            <div class="mt-2 font-display text-2xl text-ink">{{ $stats['pending_reviews'] }}</div>
            @if ($stats['pending_reviews'] > 0)
                <a href="{{ route('talent.reviews') }}" class="mt-2 inline-block text-xs text-accent-ink underline">{{ __('Moderate now') }}</a>
            @endif
        </x-ui.card>

        <x-ui.stat :label="__('Blocks · skills')" :value="$stats['blocks'].' · '.$stats['skills']" />
    </div>

    <x-ui.section class="mt-10" :title="__('Active deals')" :eyebrow="__('Whose turn')">
        @if ($activeDeals->isEmpty())
            <x-ui.card class="flex items-center justify-between gap-4">
                <p class="text-muted">{{ __('No active deals yet — enquiries from brands will open here.') }}</p>
                <a href="{{ route('talent.deals') }}" class="shrink-0 text-xs text-accent-ink underline">{{ __('All deals') }}</a>
            </x-ui.card>
        @else
            <div class="space-y-2">
                @foreach ($activeDeals as $deal)
                    <a href="{{ route('talent.deals.show', $deal) }}"
                       class="flex items-center justify-between gap-3 rounded-lg border border-line bg-surface p-4 transition hover:border-line-strong {{ $deal->status->getValue() === 'awaiting_talent' ? 'ring-1 ring-accent' : '' }}">
                        <div class="min-w-0">
                            <div class="font-mono text-[10px] uppercase tracking-wider text-subtle">{{ $deal->reference }}</div>
                            <div class="font-display text-lg text-ink">{{ $deal->title }}</div>
                            <div class="text-sm text-muted">{{ $deal->brand?->name }}</div>
                        </div>
                        <div class="shrink-0 text-end">
                            <span class="rounded-pill px-2 py-1 text-[10px] font-medium {{ $deal->status->getValue() === 'awaiting_talent' ? 'bg-accent text-on-accent' : 'border border-line bg-surface text-muted' }}">
                                {{ $deal->status->getValue() === 'awaiting_talent' ? __('Your turn') : str_replace('_', ' ', $deal->status->getValue()) }}
                            </span>
                            @if ($deal->currentStep)
                                <div class="mt-1 text-xs text-subtle">{{ $deal->currentStep->name }}</div>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </x-ui.section>

    <x-ui.section class="mt-10" :title="__('Manage your profile')">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['talent.profile.edit', __('Profile'), __('Identity, skills, username, publishing, pricing & blocks')],
                ['talent.reviews', __('Reviews'), __('Moderate testimonials')],
                ['talent.deals', __('Deals'), __('Your active bookings')],
            ] as [$route, $label, $desc])
                <a href="{{ route($route) }}" class="group rounded-lg border border-line bg-surface p-5 transition hover:border-line-strong hover:shadow-e1">
                    <div class="font-medium text-ink group-hover:text-accent-ink">{{ $label }}</div>
                    <div class="mt-1 text-sm text-muted">{{ $desc }}</div>
                </a>
            @endforeach
        </div>
    </x-ui.section>
</x-talent-layout>
