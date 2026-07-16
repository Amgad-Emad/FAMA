@php
    $logo = $brand->logo_url;
    $cover = $brand->cover_image_url;
    $credibility = $brand->credibility;
    $moods = $brand->aesthetic?->moodTags ?? collect();
    $reviews = $brand->brandReviews;
    $location = collect([$brand->base_city, $brand->base_country])->filter()->implode(', ');
    $initials = collect(preg_split('/\s+/', trim($brand->name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');

    // Aggregate the three rating axes across approved reviews.
    $axes = [];
    if ($reviews->isNotEmpty()) {
        foreach (['communication_rating' => __('Communication'), 'fairness_rating' => __('Fairness'), 'creative_respect_rating' => __('Creative respect')] as $col => $label) {
            $avg = round($reviews->avg($col), 1);
            $axes[] = ['label' => $label, 'val' => $avg, 'pct' => ($avg / 5) * 100];
        }
    }
    $overall = $reviews->isNotEmpty() ? round($reviews->avg(fn ($r) => $r->average_rating), 1) : null;

    $snapshot = collect([
        ['k' => __('Industry'), 'v' => $brand->industry ? __(ucfirst(str_replace('_', ' ', $brand->industry))) : null],
        ['k' => __('Stage'), 'v' => $brand->brand_stage ? __(ucfirst($brand->brand_stage)) : null],
        ['k' => __('Founded'), 'v' => $brand->founded_year],
        ['k' => __('HQ'), 'v' => $location ?: null],
        ['k' => __('Team size'), 'v' => $brand->company_size ? __(ucfirst($brand->company_size)) : null],
        ['k' => __('Reach'), 'v' => $brand->geographic_reach ? __(ucfirst(str_replace('_', ' ', $brand->geographic_reach))) : null],
    ])->filter(fn ($r) => filled($r['v']));
@endphp

<x-public-layout :title="$brand->name">
    {{-- COVER + IDENTITY --}}
    <section class="mx-auto max-w-6xl px-4 pt-6 sm:px-6">
        <div class="relative overflow-hidden rounded-lg border border-line"
             @style([
                 "background-image:url('$cover');background-size:cover;background-position:center" => $cover,
                 'background-color:var(--surface);background-image:repeating-linear-gradient(135deg, var(--line) 0 1px, transparent 1px 15px)' => ! $cover,
             ])>
            <div class="h-[180px] sm:h-[240px]"></div>
        </div>

        <x-ui.card elevated class="relative z-10 -mt-12 mx-0 flex flex-wrap items-end justify-between gap-6 sm:-mt-14 sm:mx-6">
            <div class="flex min-w-0 items-end gap-5">
                <span class="grid h-20 w-20 shrink-0 place-items-center overflow-hidden rounded-md bg-primary font-display text-3xl text-on-primary shadow-[0_0_0_4px_var(--elevated)] sm:h-24 sm:w-24"
                      @style(["background-image:url('$logo');background-size:cover;background-position:center" => $logo])>
                    @unless ($logo) {{ $initials !== '' ? $initials : '—' }} @endunless
                </span>
                <div class="min-w-0">
                    <div class="mb-2 flex flex-wrap items-center gap-3">
                        @if ($brand->industry)
                            <x-ui.eyebrow>{{ __(ucfirst(str_replace('_', ' ', $brand->industry))) }}</x-ui.eyebrow>
                        @endif
                        @if ($brand->is_verified)
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-success">
                                <span class="grid h-3.5 w-3.5 place-items-center rounded-pill bg-accent text-[8px] font-bold text-on-accent">✓</span>{{ __('Verified business') }}
                            </span>
                        @endif
                    </div>
                    <h1 class="font-display text-4xl leading-[0.95] tracking-tight text-ink sm:text-5xl">{{ $brand->name }}</h1>
                    <div class="mt-3 flex flex-wrap items-center gap-3 text-sm text-muted">
                        @if ($brand->brand_stage)<span>{{ __(ucfirst($brand->brand_stage)) }} {{ __('stage') }}</span>@endif
                        @if ($location)
                            <span class="h-1 w-1 rounded-pill bg-line-strong"></span>
                            <span class="inline-flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-pill bg-accent"></span>{{ $location }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if ($brand->website)
                    <x-ui.button :href="$brand->website" variant="outline" size="sm" target="_blank" rel="noopener nofollow">{{ __('Website') }} ↗</x-ui.button>
                @endif
                <x-ui.button href="#projects" variant="primary" size="sm">{{ __('View projects') }}</x-ui.button>
            </div>
        </x-ui.card>
    </section>

    {{-- BODY --}}
    <div class="mx-auto mt-6 grid max-w-6xl gap-8 px-4 sm:px-6 lg:grid-cols-[1fr_320px]">
        <div class="flex min-w-0 flex-col gap-6">
            {{-- ABOUT --}}
            @if ($brand->description || $moods->isNotEmpty())
                <section>
                    <x-ui.eyebrow>{{ __('About') }}</x-ui.eyebrow>
                    @if ($brand->description)
                        <p class="mt-3 max-w-2xl font-display text-2xl leading-snug tracking-tight text-ink sm:text-[28px]">{{ $brand->getTranslation('description', app()->getLocale()) }}</p>
                    @endif
                    @if ($moods->isNotEmpty())
                        <div class="mt-5 flex flex-wrap gap-2">
                            @foreach ($moods as $mood)
                                <x-ui.chip>{{ __(ucfirst($mood->tag)) }}</x-ui.chip>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif

            {{-- CREDIBILITY --}}
            <section>
                <x-ui.eyebrow>{{ __('Credibility') }}</x-ui.eyebrow>
                <div class="mt-4 grid grid-cols-2 gap-3.5 sm:grid-cols-3">
                    <x-ui.stat :label="__('Completed projects')" :value="$credibility?->completed_projects_count ?? 0" />
                    @if ($credibility?->response_rate_pct !== null)
                        <x-ui.stat :label="__('Response rate')" :value="$credibility->response_rate_pct.'%'" />
                    @endif
                    @if ($credibility?->avg_response_time_hours !== null)
                        <x-ui.stat :label="__('Avg. response')" :value="(int) round($credibility->avg_response_time_hours).'h'" />
                    @endif
                </div>
            </section>

            {{-- TALENT RATINGS --}}
            <section>
                <x-ui.eyebrow>{{ __('Accountability') }}</x-ui.eyebrow>
                <h2 class="mt-1 font-display text-2xl tracking-tight text-ink sm:text-3xl">{{ __('How talent rate this brand') }}</h2>
                @if ($reviews->isNotEmpty())
                    <x-ui.card class="mt-5">
                        <div class="mb-5 flex flex-wrap items-baseline gap-3">
                            <span class="font-display text-4xl leading-none text-ink">{{ $overall }}</span>
                            <span class="tracking-[2px] text-gold">★★★★★</span>
                            <span class="text-sm text-muted">{{ trans_choice('based on :count talent review|based on :count talent reviews', $reviews->count(), ['count' => $reviews->count()]) }}</span>
                        </div>
                        <div class="mb-6 flex flex-col gap-3">
                            @foreach ($axes as $i => $axis)
                                <div class="flex items-center gap-3">
                                    <span class="w-32 shrink-0 text-sm text-muted">{{ $axis['label'] }}</span>
                                    <div class="h-1.5 flex-1 overflow-hidden rounded-pill bg-line">
                                        <div class="h-full rounded-pill {{ $i === 2 ? 'bg-gold' : 'bg-accent' }}" style="width: {{ $axis['pct'] }}%"></div>
                                    </div>
                                    <span class="w-8 text-end font-mono text-xs text-ink">{{ $axis['val'] }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="grid gap-3.5 sm:grid-cols-2">
                            @foreach ($reviews as $review)
                                @if ($review->body)
                                    <div class="flex flex-col gap-3 rounded-md border border-line bg-elevated p-4">
                                        <p class="font-display text-base leading-snug text-ink">“{{ $review->body }}”</p>
                                        <div class="mt-auto flex items-center gap-2.5">
                                            <x-ui.avatar :name="$review->talent?->display_name ?? '—'" size="sm" />
                                            <span>
                                                <span class="block text-xs font-semibold text-ink">{{ $review->talent?->display_name ?? __('A talent') }}</span>
                                                <span class="block text-[11px] text-muted">★ {{ $review->average_rating }}</span>
                                            </span>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </x-ui.card>
                @else
                    <x-ui.card class="mt-5 text-center text-sm text-muted">{{ __('No reviews yet.') }}</x-ui.card>
                @endif
            </section>

            {{-- CAMPAIGNS --}}
            <section id="projects" class="scroll-mt-24">
                <x-ui.eyebrow>{{ __('On Fama') }}</x-ui.eyebrow>
                <h2 class="mt-1 font-display text-2xl tracking-tight text-ink sm:text-3xl">{{ __('Projects on FAMA') }}</h2>
                @if ($brand->projects->isNotEmpty())
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        @foreach ($brand->projects as $campaign)
                            @php $budget = collect([$campaign->budget_min, $campaign->budget_max])->filter(fn ($v) => $v !== null); @endphp
                            <a href="{{ route('brand.project.public', [$brand, $campaign]) }}"
                               class="group block overflow-hidden rounded-md border border-line bg-elevated shadow-e1 transition hover:-translate-y-0.5 hover:shadow-e3">
                                <div class="relative aspect-[16/10] bg-surface bg-cover bg-center"
                                     @style([
                                         "background-image:url('{$campaign->cover_image_url}')" => $campaign->cover_image_url,
                                         'background-image:repeating-linear-gradient(135deg, var(--line) 0 1px, transparent 1px 12px)' => ! $campaign->cover_image_url,
                                     ])>
                                    @if ($campaign->budget_is_public && $budget->isNotEmpty())
                                        <span class="absolute start-2.5 top-2.5 rounded-pill bg-surface/90 px-2.5 py-1 font-mono text-[10px] text-accent-ink backdrop-blur">{{ $budget->map(fn ($v) => number_format((float) $v))->implode('–') }} {{ $campaign->currency }}</span>
                                    @endif
                                </div>
                                <div class="flex flex-col gap-2 p-4">
                                    <div class="font-display text-lg leading-tight text-ink group-hover:text-accent-ink">{{ $campaign->title }}</div>
                                    <div class="flex items-center gap-1.5 border-t border-line pt-2 font-mono text-[11px] text-subtle">
                                        <span class="h-1.5 w-1.5 rounded-pill bg-accent"></span>{{ $campaign->location_city ?: __(ucfirst(str_replace('_', ' ', $campaign->status->getValue()))) }}
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <x-ui.card class="mt-5 text-center text-sm text-muted">{{ __('No public projects yet.') }}</x-ui.card>
                @endif
            </section>
        </div>

        {{-- SIDEBAR --}}
        <aside class="flex flex-col gap-4 lg:sticky lg:top-20 lg:self-start">
            @if ($snapshot->isNotEmpty())
                <x-ui.card elevated class="flex flex-col gap-3.5">
                    <div class="font-mono text-[10px] uppercase tracking-[0.16em] text-subtle">{{ __('Snapshot') }}</div>
                    @foreach ($snapshot as $row)
                        <div class="flex justify-between gap-3 text-sm">
                            <span class="text-muted">{{ $row['k'] }}</span>
                            <span class="text-end font-semibold text-ink">{{ $row['v'] }}</span>
                        </div>
                    @endforeach
                </x-ui.card>
            @endif

            @if ($brand->socialHandles->isNotEmpty())
                <x-ui.card elevated class="flex flex-col gap-3">
                    <div class="font-mono text-[10px] uppercase tracking-[0.16em] text-subtle">{{ __('Handles') }}</div>
                    @foreach ($brand->socialHandles as $handle)
                        <a href="{{ $handle->url ?: '#' }}" @if ($handle->url) target="_blank" rel="noopener nofollow" @endif
                           class="flex items-center justify-between gap-2.5 rounded-sm border border-line px-3 py-2 transition hover:border-accent hover:bg-accent-weak">
                            <span class="text-sm font-semibold text-ink">{{ ucfirst($handle->platform) }}</span>
                            <span class="font-mono text-xs text-muted">{{ $handle->handle }}</span>
                        </a>
                    @endforeach
                </x-ui.card>
            @endif
        </aside>
    </div>
</x-public-layout>
