<x-talent-layout :title="__('Reviews')">
    {{-- The same list the Reviews block shows on the profile-content page
         (partials/reviews-list) — one implementation, two entry points. --}}
    <x-ui.section :title="__('Reviews')" :eyebrow="__('What clients say · filter by rating')">
        @include('talent.partials.reviews-list')
    </x-ui.section>
</x-talent-layout>
