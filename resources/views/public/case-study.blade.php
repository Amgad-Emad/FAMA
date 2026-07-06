<x-public-layout :title="$study->title">
    <article class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
        <a href="{{ url($talent->slug) }}" class="font-mono text-xs uppercase tracking-[0.18em] text-accent-ink hover:underline">
            ← {{ $talent->display_name }}
        </a>

        <header class="mt-6">
            <x-ui.eyebrow>{{ trim($study->client_name.($study->year ? ' · '.$study->year : '')) }}</x-ui.eyebrow>
            <h1 class="mt-2 font-display text-4xl leading-tight text-ink sm:text-5xl">{{ $study->title }}</h1>
            @if ($study->role)
                <p class="mt-2 text-muted">{{ $study->role }}</p>
            @endif
        </header>

        @if ($study->cover_image_url)
            <img src="{{ $study->cover_image_url }}" alt="{{ $study->title }}" class="mt-8 aspect-video w-full rounded-lg border border-line object-cover">
        @else
            <div class="mt-8 aspect-video w-full rounded-lg border border-line" style="background:linear-gradient(135deg, var(--accent-weak), var(--gold-weak));"></div>
        @endif

        @if ($study->summary)
            <p class="mt-8 font-display text-xl leading-relaxed text-ink">{{ $study->summary }}</p>
        @endif

        @if (! empty($study->results))
            <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3">
                @foreach ($study->results as $label => $value)
                    <x-ui.stat :label="is_string($label) ? $label : ''" :value="is_scalar($value) ? $value : json_encode($value)" />
                @endforeach
            </div>
        @endif

        @if ($study->body)
            <div class="mt-8 space-y-4">
                @foreach (preg_split('/\n{2,}/', (string) $study->body) as $paragraph)
                    <p class="leading-relaxed text-ink">{{ $paragraph }}</p>
                @endforeach
            </div>
        @endif

        @if ($study->url)
            <a href="{{ $study->url }}" target="_blank" rel="noopener" class="mt-8 inline-block">
                <x-ui.button variant="outline">{{ __('View the project') }}</x-ui.button>
            </a>
        @endif
    </article>
</x-public-layout>
