@php
    $logo = $brand->logo_url;
    $cover = $brand->cover_image_url;
    $credibility = $brand->credibility;
    $moods = $brand->aesthetic?->moodTags ?? collect();
    $location = collect([$brand->base_city, $brand->base_country])->filter()->implode(', ');
@endphp

<x-public-layout :title="$brand->name">
    {{-- Header --}}
    <div class="mx-auto max-w-6xl px-4 pt-8 sm:px-6">
        <div class="relative overflow-hidden rounded-lg border border-line"
             @style([
                 "background-image:url('$cover');background-size:cover;background-position:center" => $cover,
                 'background-color:var(--surface);background-image:repeating-linear-gradient(135deg, var(--line) 0 1px, transparent 1px 16px)' => ! $cover,
             ])>
            <div class="h-[220px] sm:h-[300px]"></div>
        </div>

        <div class="relative z-10 -mt-14 flex flex-col gap-6 rounded-lg border border-line bg-surface p-6 shadow-e2 sm:-mt-16 sm:flex-row sm:items-end">
            <div class="-mt-20 h-24 w-24 shrink-0 overflow-hidden rounded-lg border border-line bg-elevated bg-cover bg-center shadow-e2 sm:mt-0"
                 @style(["background-image:url('$logo')" => $logo])></div>

            <div class="flex-1">
                @if ($brand->industry)
                    <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-subtle">{{ __(ucfirst(str_replace('_', ' ', $brand->industry))) }}</p>
                @endif
                <div class="mt-1 flex flex-wrap items-center gap-3">
                    <h1 class="font-display text-4xl leading-[0.95] text-ink sm:text-5xl">{{ $brand->name }}</h1>
                    @if ($brand->is_verified)
                        <span class="rounded-pill bg-accent-weak px-2.5 py-1 text-xs font-medium text-accent-ink">✓ {{ __('Verified') }}</span>
                    @endif
                </div>
                @if ($location)
                    <p class="mt-2 text-sm text-muted">{{ $location }}</p>
                @endif
                @if ($brand->description)
                    <p class="mt-3 max-w-2xl text-sm leading-relaxed text-muted">{{ $brand->getTranslation('description', app()->getLocale()) }}</p>
                @endif
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    @if ($brand->website)
                        <a href="{{ $brand->website }}" target="_blank" rel="noopener nofollow" class="rounded-pill border border-line-strong px-3 py-1.5 text-xs font-medium text-muted hover:text-ink">{{ __('Website') }} ↗</a>
                    @endif
                    @foreach ($brand->socialHandles as $handle)
                        <a href="{{ $handle->url ?: '#' }}" @if ($handle->url) target="_blank" rel="noopener nofollow" @endif
                           class="rounded-pill border border-line px-3 py-1.5 text-xs text-muted hover:text-ink">{{ ucfirst($handle->platform) }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="mx-auto mt-10 grid max-w-6xl gap-8 px-4 sm:px-6 lg:grid-cols-3">
        {{-- Left column --}}
        <div class="space-y-8 lg:col-span-2">
            {{-- Talent ratings --}}
            <section>
                <h2 class="mb-4 font-display text-2xl text-ink">{{ __('How talent rate this brand') }}</h2>
                @forelse ($brand->brandReviews as $review)
                    <div class="mb-3 rounded-lg border border-line bg-surface p-5">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-ink">{{ $review->talent?->display_name ?? __('A talent') }}</span>
                            <span class="font-display text-lg text-accent">★ {{ $review->average_rating }}</span>
                        </div>
                        <div class="mt-3 grid grid-cols-3 gap-2 text-center text-xs text-muted">
                            <div><div class="font-display text-base text-ink">{{ $review->communication_rating }}</div>{{ __('Communication') }}</div>
                            <div><div class="font-display text-base text-ink">{{ $review->fairness_rating }}</div>{{ __('Fairness') }}</div>
                            <div><div class="font-display text-base text-ink">{{ $review->creative_respect_rating }}</div>{{ __('Creative respect') }}</div>
                        </div>
                        @if ($review->body)
                            <p class="mt-3 text-sm leading-relaxed text-ink">{{ $review->body }}</p>
                        @endif
                    </div>
                @empty
                    <p class="rounded-lg border border-dashed border-line py-10 text-center text-sm text-muted">{{ __('No reviews yet.') }}</p>
                @endforelse
            </section>

            {{-- Campaigns on FAMA --}}
            <section>
                <h2 class="mb-4 font-display text-2xl text-ink">{{ __('Campaigns on FAMA') }}</h2>
                @if ($brand->campaigns->isNotEmpty())
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($brand->campaigns as $campaign)
                            <a href="{{ route('brand.campaign.public', [$brand, $campaign]) }}"
                               class="group block overflow-hidden rounded-lg border border-line bg-surface hover:border-line-strong">
                                <div class="h-36 bg-elevated bg-cover bg-center" @style(["background-image:url('{$campaign->cover_image_url}')" => $campaign->cover_image_url])></div>
                                <div class="p-4">
                                    <h3 class="font-display text-lg text-ink group-hover:text-accent">{{ $campaign->title }}</h3>
                                    <p class="mt-1 text-xs text-muted">{{ __(ucfirst(str_replace('_', ' ', $campaign->status->getValue()))) }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="rounded-lg border border-dashed border-line py-10 text-center text-sm text-muted">{{ __('No public campaigns yet.') }}</p>
                @endif
            </section>
        </div>

        {{-- Right column --}}
        <aside class="space-y-6">
            {{-- Credibility --}}
            <section class="rounded-lg border border-line bg-surface p-5">
                <h3 class="mb-4 font-mono text-[11px] uppercase tracking-[0.18em] text-subtle">{{ __('Track record') }}</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-muted">{{ __('Completed projects') }}</dt>
                        <dd class="font-display text-lg text-ink">{{ $credibility?->completed_projects_count ?? 0 }}</dd>
                    </div>
                    @if ($credibility?->response_rate_pct !== null)
                        <div class="flex items-center justify-between">
                            <dt class="text-muted">{{ __('Response rate') }}</dt>
                            <dd class="font-display text-lg text-ink">{{ $credibility->response_rate_pct }}%</dd>
                        </div>
                    @endif
                    @if ($credibility?->avg_response_time_hours !== null)
                        <div class="flex items-center justify-between">
                            <dt class="text-muted">{{ __('Avg. response') }}</dt>
                            <dd class="font-display text-lg text-ink">{{ (int) round($credibility->avg_response_time_hours) }}h</dd>
                        </div>
                    @endif
                </dl>
            </section>

            {{-- Aesthetic --}}
            @if ($moods->isNotEmpty())
                <section class="rounded-lg border border-line bg-surface p-5">
                    <h3 class="mb-3 font-mono text-[11px] uppercase tracking-[0.18em] text-subtle">{{ __('Aesthetic') }}</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($moods as $mood)
                            <span class="rounded-pill bg-elevated px-3 py-1 text-xs text-muted">{{ __(ucfirst($mood->tag)) }}</span>
                        @endforeach
                    </div>
                </section>
            @endif
        </aside>
    </div>
</x-public-layout>
