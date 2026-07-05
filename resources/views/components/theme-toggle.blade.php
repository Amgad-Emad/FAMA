<button
    type="button"
    x-data="{
        dark: document.documentElement.getAttribute('data-theme') === 'dark',
        toggle() {
            this.dark = !this.dark;
            document.documentElement.setAttribute('data-theme', this.dark ? 'dark' : 'light');
            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        }
    }"
    @click="toggle()"
    :aria-pressed="dark.toString()"
    aria-label="{{ __('Toggle theme') }}"
    {{ $attributes->merge(['class' => 'grid h-9 w-9 place-items-center rounded-pill border border-line-strong bg-primary text-on-primary transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent']) }}
>
    <svg x-show="!dark" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
    </svg>
    <svg x-show="dark" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="4" />
        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" />
    </svg>
</button>
