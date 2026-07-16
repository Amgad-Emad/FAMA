@php
    $cover = $campaign->cover_image_url;
    $budget = collect([$campaign->budget_min, $campaign->budget_max])->filter(fn ($v) => $v !== null);
    $location = collect([$campaign->location_city, $campaign->location_country])->filter()->implode(', ');
    $dates = collect([$campaign->start_date?->translatedFormat('M j'), $campaign->end_date?->translatedFormat('M j, Y')])->filter()->implode(' – ');
    $status = $campaign->status->getValue();
    $logo = $brand->logo_url;
    $initials = collect(preg_split('/\s+/', trim($brand->name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');

    // Budget is only shown publicly when the brand opts in (private by default).
    $meta = collect([
        ['k' => __('Budget'), 'v' => ($campaign->budget_is_public && $budget->isNotEmpty()) ? $budget->map(fn ($v) => number_format((float) $v))->implode(' – ').' '.$campaign->currency : null],
        ['k' => __('Dates'), 'v' => $dates ?: null],
        ['k' => __('Location'), 'v' => $location ?: null],
        ['k' => __('Role'), 'v' => $campaign->talentType?->getTranslation('name', app()->getLocale())],
    ])->filter(fn ($r) => filled($r['v']));
@endphp

<x-public-layout :title="$campaign->title">
    <div class="mx-auto max-w-6xl px-4 pt-6 sm:px-6">
        <a href="{{ route('brand.public', $brand) }}" class="mb-4 inline-flex items-center gap-1.5 text-sm text-muted transition hover:text-accent-ink">
            <span class="text-base rtl:rotate-180">‹</span>{{ $brand->name }}
        </a>

        {{-- COVER with title overlay --}}
        <div class="relative overflow-hidden rounded-lg border border-line">
            <div class="aspect-[21/9] min-h-[240px] bg-surface bg-cover bg-center"
                 @style([
                     "background-image:url('$cover')" => $cover,
                     'background-image:repeating-linear-gradient(135deg, var(--line) 0 1px, transparent 1px 15px)' => ! $cover,
                 ])></div>
            <div class="absolute inset-0" style="background:linear-gradient(to top, rgba(10,8,6,.66), rgba(10,8,6,.15) 52%, transparent)"></div>
            <span class="absolute start-4 top-4 inline-flex items-center gap-2 rounded-pill px-3.5 py-1.5 text-xs font-semibold text-white"
                  style="background:color-mix(in srgb, var(--accent) 90%, #000)">
                <span class="h-1.5 w-1.5 rounded-pill bg-white"></span>{{ __(ucfirst(str_replace('_', ' ', $status))) }}
            </span>
            <div class="absolute inset-x-0 bottom-0 p-5 sm:p-8">
                <div class="mb-2 font-mono text-[11px] uppercase tracking-[0.2em] text-white/80">{{ __('by') }} {{ $brand->name }}</div>
                <h1 class="max-w-[18ch] font-display text-4xl leading-[1.02] tracking-tight text-white sm:text-6xl">{{ $campaign->title }}</h1>
            </div>
        </div>

        {{-- META STRIP --}}
        @if ($meta->isNotEmpty())
            <div class="mt-5 grid grid-cols-2 overflow-hidden rounded-md border border-line sm:grid-cols-4" style="gap:1px;background:var(--line)">
                @foreach ($meta as $cell)
                    <div class="bg-surface px-4 py-4">
                        <div class="font-mono text-[10px] uppercase tracking-wider text-subtle">{{ $cell['k'] }}</div>
                        <div class="mt-1 font-display text-xl text-ink">{{ $cell['v'] }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-6 grid gap-8 lg:grid-cols-[1fr_320px]">
            <div class="flex min-w-0 flex-col gap-6">
                {{-- BRIEF --}}
                @if ($campaign->description)
                    <section>
                        <x-ui.eyebrow>{{ __('The brief') }}</x-ui.eyebrow>
                        <p class="mt-3 max-w-2xl font-display text-2xl leading-snug tracking-tight text-ink sm:text-[28px]">{{ $campaign->getTranslation('description', app()->getLocale()) }}</p>
                    </section>
                @endif

                {{-- ROLE SOUGHT — one role, one position. --}}
                @if ($campaign->talentType)
                    <section>
                        <x-ui.eyebrow>{{ __('Casting') }}</x-ui.eyebrow>
                        <h2 class="mt-1 font-display text-2xl tracking-tight text-ink sm:text-3xl">{{ __('Role sought') }}</h2>
                        <div class="mt-5 flex flex-wrap items-center justify-between gap-4 rounded-md border border-line bg-surface px-5 py-4">
                            <div class="flex items-center gap-3">
                                <span class="font-display text-xl text-ink">{{ $campaign->talentType->getTranslation('name', app()->getLocale()) }}</span>
                                <span class="rounded-pill bg-accent-weak px-2.5 py-1 font-mono text-[11px] text-accent-ink">× 1</span>
                            </div>
                            <a href="{{ url('/discover') }}?filter[type]={{ $campaign->talentType->slug }}" class="font-mono text-xs text-muted transition hover:text-accent-ink">{{ __('Find talent') }} →</a>
                        </div>
                    </section>
                @endif

                {{-- MOOD BOARD --}}
                @if ($campaign->gallery->isNotEmpty())
                    <section>
                        <x-ui.eyebrow>{{ __('Mood board') }}</x-ui.eyebrow>
                        <h2 class="mt-1 font-display text-2xl tracking-tight text-ink sm:text-3xl">{{ __('Gallery') }}</h2>
                        <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
                            @foreach ($campaign->gallery as $item)
                                <div class="group relative aspect-square overflow-hidden rounded-md border border-line">
                                    <img src="{{ $item->thumbnail_url ?: $item->media_url }}" alt="{{ $item->getTranslation('caption', app()->getLocale()) }}" class="h-full w-full object-cover transition group-hover:scale-[1.02]" loading="lazy">
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            {{-- SIDEBAR --}}
            <aside class="lg:sticky lg:top-20 lg:self-start">
                <x-ui.card elevated class="flex flex-col gap-4">
                    <a href="{{ route('brand.public', $brand) }}" class="flex items-center gap-3 transition hover:opacity-85">
                        <span class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-md bg-primary font-display text-lg text-on-primary"
                              @style(["background-image:url('$logo');background-size:cover;background-position:center" => $logo])>
                            @unless ($logo) {{ $initials !== '' ? $initials : '—' }} @endunless
                        </span>
                        <span>
                            <span class="block font-display text-lg leading-tight text-ink">{{ $brand->name }}</span>
                            @if ($brand->is_verified)
                                <span class="mt-0.5 flex items-center gap-1.5 text-[11px] text-success">
                                    <span class="grid h-3 w-3 place-items-center rounded-pill bg-accent text-[7px] font-bold text-on-accent">✓</span>{{ __('Verified') }}
                                </span>
                            @endif
                        </span>
                    </a>
                    <div class="h-px bg-line"></div>
                    <div class="flex flex-col gap-2.5">
                        @foreach ($meta as $cell)
                            <div class="flex justify-between gap-3 text-sm">
                                <span class="text-muted">{{ $cell['k'] }}</span>
                                <span class="text-end font-semibold text-ink">{{ $cell['v'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    @auth('talent')
                        <div x-data="applyModal({ projectId: {{ $campaign->id }}, labels: { empty: @js(__('Write a short brief before applying.')), tooBig: @js(__('A file is too large (max 10MB).')) } })">
                            <x-ui.button type="button" variant="primary" class="w-full" x-on:click="open()">{{ __('Apply') }}</x-ui.button>
                            @include('brand.partials.apply-modal', ['project' => $campaign, 'brand' => $brand])
                        </div>
                    @else
                        {{-- Guests / brands → talent login (the applicant must be a talent). --}}
                        <x-ui.button :href="route('login', ['role' => 'talent'])" variant="primary" class="w-full">{{ __('Apply') }}</x-ui.button>
                    @endauth
                    <x-ui.button :href="route('brand.public', $brand)" variant="outline" class="mt-2 w-full">{{ __('View brand') }}</x-ui.button>
                </x-ui.card>
            </aside>
        </div>
    </div>
</x-public-layout>
