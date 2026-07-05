@php $agencies = $talent->agencyAffiliations; @endphp

@if ($agencies->isNotEmpty())
    <x-ui.section :title="$block->title ?: $block->blockType->name" :eyebrow="__('Representation')">
        <div class="grid gap-2 sm:grid-cols-2">
            @foreach ($agencies as $agency)
                <div class="flex items-center gap-3 rounded-md border border-line bg-surface px-4 py-3">
                    <x-ui.avatar :src="$agency->agency_logo_url" :name="$agency->agency_name" size="sm" />
                    <div class="flex-1">
                        <div class="text-sm font-medium text-ink">{{ $agency->agency_name }}</div>
                        <div class="font-mono text-[11px] uppercase tracking-wider text-subtle">
                            {{ __(ucfirst(str_replace('_', ' ', $agency->representation_type))) }}{{ $agency->region ? ' · '.$agency->region : '' }}
                        </div>
                    </div>
                    @unless ($agency->is_current)
                        <x-ui.chip tone="neutral">{{ __('Past') }}</x-ui.chip>
                    @endunless
                </div>
            @endforeach
        </div>
    </x-ui.section>
@endif
