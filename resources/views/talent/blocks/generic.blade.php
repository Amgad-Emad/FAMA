{{-- Fallback for inline / unmapped block types. --}}
@php $lines = array_filter((array) ($block->content ?? []), fn ($l) => $l !== null && $l !== ''); @endphp
<x-profile.block :block="$block" :empty="empty($lines)" :is-owner="$isOwner ?? false">
    <div class="card tight" style="margin-top:var(--s4)">
        @foreach ($lines as $line)
            <p>{{ is_scalar($line) ? $line : json_encode($line, JSON_UNESCAPED_UNICODE) }}</p>
        @endforeach
    </div>
</x-profile.block>
