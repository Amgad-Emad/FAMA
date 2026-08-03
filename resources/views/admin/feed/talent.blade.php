<x-admin-layout :title="__('Feed activity — :name', ['name' => $talent->display_name])">
    {{-- One talent's feed footprint (Prompt 5Q): posts, comments and reports
         against their content, one timeline. --}}
    <div class="space-y-5">
        <div>
            <a href="{{ route('admin.moderation.feed') }}" class="text-xs text-muted hover:text-ink">← {{ __('Feed moderation') }}</a>
            <h2 class="mt-1 text-lg font-semibold">{{ $talent->display_name }} <span class="text-sm text-muted">{{ '@'.$talent->slug }}</span></h2>
        </div>

        <div class="overflow-hidden rounded-lg border border-line bg-surface">
            @forelse ($timeline as $entry)
                <div class="flex flex-wrap items-center gap-3 border-b border-line px-4 py-3 text-sm last:border-b-0">
                    <span class="w-20 text-xs uppercase text-muted">{{ $entry['kind'] }}</span>
                    <span class="text-xs text-muted">{{ $entry['at']?->toDayDateTimeString() }}</span>
                    @if ($entry['kind'] === 'report')
                        <span class="rounded-pill bg-warn-weak px-2 py-0.5 text-xs text-warn">{{ $entry['row']->reason }}</span>
                        <span class="min-w-0 flex-1 truncate">{{ str($entry['row']->note ?? '')->limit(120) }}</span>
                        <span class="text-xs text-muted">{{ $entry['row']->status }}</span>
                    @else
                        <span class="min-w-0 flex-1 truncate">{{ str(strip_tags((string) $entry['row']->body))->limit(140) ?: '—' }}</span>
                        <span class="text-xs text-muted">{{ $entry['row']->state->getValue() }}</span>
                    @endif
                </div>
            @empty
                <p class="p-6 text-sm text-muted">{{ __('No feed activity yet.') }}</p>
            @endforelse
        </div>
    </div>
</x-admin-layout>
