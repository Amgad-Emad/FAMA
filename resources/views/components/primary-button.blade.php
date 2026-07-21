<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-pill bg-primary px-4 py-2.5 text-sm font-medium text-on-primary transition select-none hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-bg']) }}>
    {{ $slot }}
</button>
