<x-talent-layout :title="__('Dashboard')">
    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
            <x-ui.eyebrow>{{ __('Welcome back') }}</x-ui.eyebrow>
            <h2 class="mt-1 font-display text-3xl text-ink">{{ $talent->display_name ?: $talent->email }}</h2>
        </div>
        <x-ui.badge :status="$talent->availability_status->getValue()" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.card>
            <div class="font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Profile status') }}</div>
            <div class="mt-2 flex items-center gap-2">
                <span class="h-2.5 w-2.5 rounded-pill {{ $stats['is_published'] ? 'bg-success' : 'bg-warn' }}"></span>
                <span class="font-display text-xl text-ink">{{ $stats['is_published'] ? __('Live') : __(ucfirst($stats['status'])) }}</span>
            </div>
            <a href="{{ route('talent.account') }}" class="mt-3 inline-block text-xs text-accent-ink underline">
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

        <x-ui.stat :label="__('Blocks · professions')" :value="$stats['blocks'].' · '.$stats['professions']" />
    </div>

    <x-ui.section class="mt-10" :title="__('Active deals')" :eyebrow="__('Whose turn')">
        <x-ui.card class="flex items-center justify-between gap-4">
            <div>
                <p class="text-ink">{{ __('Your deals will appear here.') }}</p>
                <p class="mt-1 text-sm text-muted">{{ __('The deal engine, inbox and “whose turn” live tracking arrive in Phase 1E.') }}</p>
            </div>
            <span class="shrink-0 font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Coming soon') }}</span>
        </x-ui.card>
    </x-ui.section>

    <x-ui.section class="mt-10" :title="__('Manage your profile')">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['talent.profile.edit', __('Profile editor'), __('Blocks, layout & core details')],
                ['talent.professions', __('Professions'), __('Your creative disciplines')],
                ['talent.services', __('Rate card'), __('Services & pricing')],
                ['talent.reviews', __('Reviews'), __('Moderate testimonials')],
                ['talent.availability', __('Availability'), __('Status, travel & rate tier')],
                ['talent.account', __('Account'), __('Slug, publishing & prefs')],
            ] as [$route, $label, $desc])
                <a href="{{ route($route) }}" class="group rounded-lg border border-line bg-surface p-5 transition hover:border-line-strong hover:shadow-e1">
                    <div class="font-medium text-ink group-hover:text-accent-ink">{{ $label }}</div>
                    <div class="mt-1 text-sm text-muted">{{ $desc }}</div>
                </a>
            @endforeach
        </div>
    </x-ui.section>
</x-talent-layout>
