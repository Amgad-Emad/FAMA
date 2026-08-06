{{--
    Canonical contract-document renderer (ADR-V). Given a normalized $doc from
    App\Contracting\ContractDocument, it paints the editorial "paper" contract used
    by both the admin template builder's live preview and the talent/brand contract
    room. Presentation only — all resolution happens in ContractDocument.
--}}
@php($doc = $doc ?? [])
<article class="contract-paper rounded-2xl border border-line bg-elevated p-6 sm:p-8 shadow-e1">
    <div class="mb-6 text-end font-mono text-[10px] uppercase tracking-[0.15em] text-subtle">
        {{ $doc['org'] ?? 'FAMA' }} <span class="text-line-strong">/</span> {{ $doc['ref'] ?? 'DRAFT' }}
    </div>

    <h3 class="font-display text-xl leading-snug text-ink sm:text-2xl">{{ $doc['intro'] ?? '' }}</h3>

    {{-- Summary rows --}}
    <dl class="mt-6 divide-y divide-line/70">
        @foreach (($doc['rows'] ?? []) as $row)
            <div class="flex items-baseline justify-between gap-6 py-3">
                <dt class="shrink-0 text-sm text-muted">{{ $row['label'] }}</dt>
                <dd class="text-end {{ ($row['strong'] ?? false) ? 'font-semibold text-ink' : 'text-ink' }}">
                    {{ $row['value'] ?? '—' }}
                </dd>
            </div>
        @endforeach
    </dl>

    {{-- Exception (or the standard "no exceptions" note) --}}
    @if (! empty($doc['exception']))
        <div class="mt-6 rounded-lg border-s-[3px] border-warn bg-warn-weak p-3 text-sm text-ink">
            <span class="font-mono text-[10px] uppercase tracking-wider text-warn">{{ __('Exception') }}</span>
            <p class="mt-1 whitespace-pre-wrap">{{ $doc['exception'] }}</p>
        </div>
    @else
        <p class="mt-6 rounded-lg border border-dashed border-line py-3 text-center text-sm italic text-subtle">
            {{ __('No exceptions — standard terms apply') }}
        </p>
    @endif

    {{-- Standard terms --}}
    @if (! empty($doc['clauses']))
        <div class="mt-7 border-t border-line pt-5">
            <p class="mb-3 font-mono text-[10px] uppercase tracking-[0.15em] text-subtle">{{ __('Standard terms') }}</p>
            <ul class="space-y-2.5">
                @foreach ($doc['clauses'] as $clause)
                    <li class="flex gap-2 text-sm leading-relaxed">
                        <span class="select-none text-subtle">—</span>
                        <span class="text-ink"><span class="font-medium">{{ $clause['label'] }}</span>@if (! empty($clause['label']) && ! empty($clause['body'])) — @endif<span class="text-muted">{{ $clause['body'] }}</span></span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-7 border-t border-line pt-4 font-mono text-[10px] uppercase tracking-wider {{ ($doc['confirmed'] ?? false) ? 'text-success' : 'text-subtle' }}">
        {{ ($doc['confirmed'] ?? false) ? __('Confirmed') : __('Not yet confirmed') }}
    </div>
</article>
