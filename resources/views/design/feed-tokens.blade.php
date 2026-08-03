<x-public-layout :title="'Daylight — feed tokens'">
    @include('feed.partials.daylight-head')
    @include('feed.partials.glyphs')

    <div class="mx-auto max-w-6xl px-5 py-10 space-y-10">
        <header>
            <h1 class="text-2xl font-semibold">Daylight — feed token preview</h1>
            <p class="mt-1 text-sm text-muted">Every token, type role, frame variant and state — both themes × both directions. Review at a glance; feed-scoped only.</p>
        </header>

        @foreach (['light' => 'Daylight', 'dark' => 'Night Shoot'] as $theme => $themeLabel)
            @foreach (['ltr' => 'LTR', 'rtl' => 'RTL'] as $dir => $dirLabel)
                <section data-theme="{{ $theme }}" dir="{{ $dir }}">
                    <div data-surface="daylight" class="rounded-2xl p-8 space-y-8" style="background: var(--dl-ground); color: var(--dl-ink);">
                        <p class="t-meta">{{ $themeLabel }} · {{ $dirLabel }}</p>

                        {{-- Colour tokens --}}
                        <div class="flex flex-wrap gap-3">
                            @foreach (['ground', 'ground-sunk', 'surface', 'surface-sunk', 'seam', 'ink', 'ink-soft', 'ink-faint', 'daylight', 'veil'] as $token)
                                <div class="text-center">
                                    <div class="h-14 w-20 rounded-md border border-seam" style="background: var(--dl-{{ $token }})"></div>
                                    <span class="t-meta">{{ $token }}</span>
                                </div>
                            @endforeach
                        </div>

                        {{-- Type roles --}}
                        <div class="space-y-3">
                            <p class="t-meta">12 MONO META — 14:32 · 03/08 · PHOTOGRAPHY</p>
                            <p class="t-body">15 body — {{ $dir === 'rtl' ? 'الضوء هو طاقة العلامة. سطح دافئ وظل أزرق بارد.' : 'Light is the brand’s energy. Warm ground, cold light.' }}</p>
                            <p class="t-comment">17 comment — {{ $dir === 'rtl' ? 'الإضاءة دي تحفة بجد.' : 'The light in this set is unreal.' }}</p>
                            <p class="t-note">{{ $dir === 'rtl' ? 'قُصّت القطع على المقاس، لا على الباترون.' : 'Cut like a garment, not a pattern.' }}</p>
                            <p class="t-display">{{ $dir === 'rtl' ? 'ضوء النهار' : 'Daylight' }}</p>
                        </div>

                        {{-- Frames --}}
                        <div class="flex flex-wrap items-start gap-6">
                            <div class="frame-shadow">
                                <div class="frame p-5" style="inline-size: 220px;">
                                    <p class="t-meta">frame</p>
                                    <p class="t-body mt-1">Notched inline-end, blue shadow, no border.</p>
                                </div>
                            </div>
                            <div class="frame-shadow frame-shadow--raised">
                                <div class="frame p-5" style="inline-size: 220px;">
                                    <p class="t-meta">frame — raised</p>
                                    <p class="t-body mt-1">Hover / focus elevation.</p>
                                </div>
                            </div>
                            <div class="skeleton frame p-5" style="inline-size: 220px; block-size: 90px;"></div>
                        </div>

                        {{-- Interactive states --}}
                        <div class="flex flex-wrap items-center gap-6">
                            <button type="button" class="glint" aria-pressed="false">
                                <span class="glint-mark" aria-hidden="true">✦</span>
                                <span class="t-meta">Like · 24</span>
                            </button>
                            <button type="button" class="glint" aria-pressed="true">
                                <span class="glint-mark" aria-hidden="true">✦</span>
                                <span class="t-meta">Liked · 25</span>
                            </button>
                            <span class="feed-chip" style="--chip-glint: #E1306C;">[ig] {{ '@' }}amgad</span>
                            <a href="#" class="feed-mention">{{ '@' }}rana</a>
                            <span class="well px-3 py-1.5 t-body">input well</span>
                        </div>

                        {{-- Controls (5T): rest / ready / busy · chip states · wells --}}
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-3">
                                <button type="button" class="dl-btn">{{ __('SEND') }} · {{ __('ghost') }}</button>
                                <button type="button" class="dl-btn is-ready">{{ __('SEND') }} · {{ __('ready') }}</button>
                                <button type="button" class="dl-btn is-ready is-busy"><span class="dl-spinner" aria-hidden="true"></span>{{ __('SENDING') }}</button>
                                <span class="dl-chip">{{ __('CHIP') }}</span>
                                <span class="dl-chip is-active">{{ __('ACTIVE') }}</span>
                            </div>
                            <textarea rows="1" class="well well--grow t-comment w-full p-3" placeholder="{{ __('A well — focus for the halo') }}"></textarea>
                            <div class="thread-row is-mine ps-3">
                                <p class="t-meta">{{ __('MY COMMENT — the one accent in the thread') }}</p>
                            </div>
                            <p class="thread-tombstone t-meta">{{ __('This comment was removed.') }}</p>
                        </div>

                        {{-- Action bar (5AD): 40px hits, hover wash, lit like, end cluster --}}
                        <div class="action-bar" style="border-block-start: 1px solid var(--dl-seam);">
                            <button type="button" class="action-btn is-lit" aria-pressed="true">
                                <span class="glint-mark" aria-hidden="true">✦</span><span class="action-count">24</span>
                            </button>
                            <button type="button" class="action-btn"><span aria-hidden="true">✳</span><span class="action-count">4</span></button>
                            <button type="button" class="action-btn"><span aria-hidden="true">↗</span></button>
                            <span class="action-end flex items-center gap-1">
                                <button type="button" class="action-btn action-btn--sm">{{ __('SAVE') }}</button>
                                <button type="button" class="action-btn action-btn--sm" aria-haspopup="menu">···</button>
                            </span>
                        </div>

                        {{-- Comment typography (5AG): author line + badge + avatar fallback --}}
                        <div class="flex items-center gap-2">
                            <span class="comment-avatar avatar-fallback" style="background: color-mix(in oklch, var(--dl-surface-sunk), var(--dl-veil) 35%); font-family: var(--dl-font-display);">R</span>
                            <span class="comment-author">Rana El-Sherif</span>
                            <bdi class="comment-handle">{{ '@' }}rana</bdi>
                            <span class="comment-badge">{{ __('BRAND') }}</span>
                            <span class="comment-time">2H AGO</span>
                        </div>

                        {{-- Credits block --}}
                        <div>
                            <hr class="seam mb-3">
                            <p class="credits">PHOTOGRAPHY — <a href="#">{{ '@' }}rana</a> / STYLING — <a href="#">{{ '@' }}hossam</a> / MODELING — <a href="#">{{ '@' }}laila</a></p>
                        </div>
                    </div>
                </section>
            @endforeach
        @endforeach
    </div>
</x-public-layout>
