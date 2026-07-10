<x-brand-layout :title="__('Pending enquiries')">
    <div x-data="brandEnquiries()" class="space-y-5">
        <p class="text-sm text-muted">{{ __('Booking enquiries sent to you before signing up. Convert one into a deal to start the loop.') }}</p>

        <div x-show="loading" class="py-10 text-center text-sm text-muted">{{ __('Loading…') }}</div>

        <div x-show="!loading" class="space-y-3">
            <template x-for="enquiry in enquiries" :key="enquiry.id">
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line bg-surface p-5">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-ink">
                            <span x-text="enquiry.contact_name"></span>
                            <span class="text-muted" x-show="enquiry.talent"> · {{ __('for') }} <span x-text="enquiry.talent?.display_name"></span></span>
                        </p>
                        <p class="mt-1 line-clamp-2 text-sm text-muted" x-text="enquiry.brief"></p>
                        <p class="mt-1 text-xs text-subtle" x-show="enquiry.service" x-text="enquiry.service?.name"></p>
                    </div>
                    <button @click="convert(enquiry.id)" :disabled="converting === enquiry.id"
                            class="rounded-pill bg-accent px-4 py-2 text-sm font-medium text-on-primary hover:opacity-90 disabled:opacity-50"
                            x-text="converting === enquiry.id ? '{{ __('Converting…') }}' : '{{ __('Convert to deal') }}'"></button>
                </div>
            </template>
            <template x-if="!enquiries.length">
                <p class="rounded-lg border border-dashed border-line py-10 text-center text-sm text-muted">{{ __('No pending enquiries.') }}</p>
            </template>
        </div>
    </div>
</x-brand-layout>
