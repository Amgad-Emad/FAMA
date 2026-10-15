{{-- The talent's Reviews page (read-only, filter by rating) on the fama-ui
     profile-content styles — the same list the Reviews block shows there. --}}
<x-fama.layout :title="__('Reviews')" mode="talent">
    <x-slot:styles>
        <link rel="stylesheet" href="{{ \App\Support\FamaAsset::url('fama-ui/css/pages/profile-content.css') }}">
    </x-slot:styles>
    <x-slot:hero>
        <x-fama.hero :label="__('Fill in your profile')" :title="__('Reviews')" :lead="__('Written by clients. Our team approves each one before it shows on your profile.')" />
    </x-slot:hero>
    <main class="wrap">
        <section class="block">
            @include('talent.partials.reviews-list')
        </section>
    </main>
</x-fama.layout>
