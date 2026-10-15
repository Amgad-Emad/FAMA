{{--
    Profile content (GET /talent/content/{section?}) — the fama-ui
    "profile-content.html" screen. One row of section buttons (the profile's own
    sections: Profile-wide, then each skill, in the talent's order); all of a
    section's blocks stack below. Switching section swaps the blocks in place
    (`contentTabs` fetches the section's page and replaces #blocks — no reload).
    Each block's items: `contentBlock` (resources/js/ui/profile.js) on the
    existing CRUD endpoints. Reviews show only while features.reviews is on.
--}}
@php
    $desc = [
        'gallery' => __('Photos, videos and embeds. Drag to reorder; select one to edit its caption.'),
        'look_types' => __('Each look is a named set of images.'),
        'digitals' => __('Plain, unretouched shots so people see you as you are now.'),
        'showreel' => __('Your reel, from YouTube, Vimeo or an upload.'),
        'equipment' => __('The kit you work with.'),
        'software_stack' => __('The tools you work in.'),
        'brand_collabs' => __('Brands you\'ve worked with, with the project and year.'),
        'reviews' => __('Written by clients. Our team approves each one before it shows on your profile.'),
        'recognitions' => __('Peers vouching for you. Each is approved before it shows.'),
    ];
    $labels = [
        'caption' => __('Caption'), 'media_type' => __('Type'), 'embed_url' => __('Embed link'),
        'name' => __('Name'), 'shot_type' => __('Shot type'), 'captured_at' => __('Date taken'),
        'title' => __('Title'), 'video_url' => __('Video link'), 'platform' => __('Platform'), 'duration_seconds' => __('Length (seconds)'),
        'category' => __('Category'), 'brand' => __('Brand'), 'model' => __('Model'), 'notes' => __('Notes'),
        'software_name' => __('Name'), 'proficiency' => __('Level'),
        'brand_name' => __('Brand name'), 'project_title' => __('Project title'), 'year' => __('Year'), 'url' => __('Link'),
    ];
    $options = [
        'image' => __('Image'), 'video' => __('Video'), 'embed' => __('Embed'),
        'front' => __('Front'), 'side' => __('Side'), 'back' => __('Back'), 'full' => __('Full'), 'headshot' => __('Headshot'), 'smile' => __('Smile'),
        'youtube' => 'YouTube', 'vimeo' => 'Vimeo', 'self_hosted' => __('Uploaded'),
        'camera' => __('Camera'), 'lens' => __('Lens'), 'lighting' => __('Lighting'), 'audio' => __('Audio'), 'grip' => __('Grip'), 'drone' => __('Drone'), 'accessory' => __('Accessory'),
        'beginner' => __('Beginner'), 'intermediate' => __('Intermediate'), 'advanced' => __('Advanced'), 'expert' => __('Expert'),
    ];
    $strings = [
        'savedT' => __(':name saved'), 'deleted' => __(':name deleted'),
        'delTitle' => __('Delete :name?'), 'delHelp' => __('It comes off your profile.'), 'delOk' => __('Delete'),
        'options' => $options,
    ];
    $reviewsOn = \App\Support\Features::enabled('reviews');
    $panels = array_values(array_filter($panels, fn ($p) => $reviewsOn || $p['kind'] !== 'reviews'));
@endphp

<x-fama.layout :title="__('Profile content')" mode="talent">
    <x-slot:styles>
        <link rel="stylesheet" href="{{ asset('fama-ui/css/pages/profile-content.css') }}?v=1">
    </x-slot:styles>

    <x-slot:hero>
        <x-fama.hero :label="__('Fill in your profile')" :title="__('Profile content')"
                     :lead="__('The parts of your profile around your projects: recognitions, brands and what you’d like to explore.')">
            <a class="fx-hbtn light" href="{{ route('talent.profile.edit') }}#sections">{{ __('Show or hide sections') }}</a>
        </x-fama.hero>
    </x-slot:hero>

<main class="wrap" x-data="contentTabs()">
  @if (count($sections) > 1)
    <div class="tabs" role="tablist" id="tabs" aria-label="{{ __('Profile sections') }}" @keydown.arrow-right.prevent="step(1)" @keydown.arrow-left.prevent="step(-1)">
      @foreach ($sections as $section)
        <button type="button" role="tab" data-url="{{ route('talent.content', ['section' => $section['key']]) }}" data-s="{{ $section['key'] }}"
                aria-selected="{{ $section['key'] === $active ? 'true' : 'false' }}" @click="open($el)">{{ $section['label'] }}<span class="n">{{ $section['count'] }}</span></button>
      @endforeach
    </div>
  @endif
  <p class="meta tabhint" id="tabhint">{{ __('Every change saves in place.') }}</p>

  <div id="blocks" role="tabpanel" x-ref="blocks" :aria-busy="loading.toString()" :style="loading ? 'opacity:.55' : ''">
    @forelse ($panels as $panel)
      @php
        $isOn = $panel['is_visible'];
        $help = $isOn ? ($panel['kind'] === 'link' ? $panel['blurb'] : ($desc[$panel['type']] ?? '')) : __('Hidden from your profile. It keeps its content, and you can show it again in Profile structure.');
      @endphp
      <section class="block{{ $isOn ? '' : ' off' }}" id="block-{{ $panel['type'] }}" data-b="{{ $panel['type'] }}" aria-label="{{ $panel['title'] }}"
        @if ($panel['kind'] === 'content')
          x-data="contentBlock({ endpoint: @js('/talent/content/'.$panel['type']), blank: @js(collect($panel['config']['fields'])->mapWithKeys(fn ($f) => [$f['name'] => $f['kind'] === 'translatable' ? ['en' => '', 'ar' => ''] : ''])->put('position', null)->all()), fields: @js($panel['config']['fields']), media: @js($panel['config']['media']), label: @js($panel['title']), strings: @js($strings + ['labels' => $labels + ($panel['type'] === 'look_types' ? ['name' => __('Look name')] : [])]) })"
        @endif>
        <div class="bhead"><h2 class="h2">{{ $panel['title'] }}</h2>@unless ($isOn)<span class="tag tag-hidden">{{ __('Hidden') }}</span>@endunless
          <span class="acts">
            @if ($panel['kind'] === 'reviews')
              <a class="link" href="{{ route('talent.reviews') }}">{{ __('All reviews') }}<svg class="icon flip" style="width:16px;height:16px" aria-hidden="true"><use href="#i-arrow"/></svg></a>
            @elseif ($panel['kind'] === 'content' && $panel['type'] !== 'gallery')
              <button type="button" class="btn btn-2" x-show="editing === null" @click="startAdd()"><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg>{{ __('Add') }}</button>
            @endif
          </span></div>
        <p class="bdesc">{{ $help }}</p>

        @if ($panel['kind'] === 'reviews')
          @include('talent.partials.reviews-list')
        @elseif ($panel['kind'] === 'recognitions')
          @if (count($panel['items']))
            <ul class="revs">
              @foreach ($panel['items'] as $rec)
                <li class="rev rec"><div class="top"><span @class(['pill', 'p-live' => $rec['status'] === 'approved', 'p-bad' => $rec['status'] === 'rejected', 'p-off' => ! in_array($rec['status'], ['approved', 'rejected'], true)])>{{ __(ucfirst($rec['status'])) }}</span></div>
                  <q style="margin-top:var(--s3)">{{ $rec['body'] }}</q><p class="who">— {{ $rec['who'] }}</p></li>
              @endforeach
            </ul>
          @else
            <p class="none">{{ __('Nothing here yet.') }}</p>
          @endif
        @elseif ($panel['kind'] === 'link')
          <p style="margin-top:var(--s4)"><a class="link" href="{{ $panel['url'] }}">{{ $panel['cta'] }}<svg class="icon flip" aria-hidden="true"><use href="#i-arrow"/></svg></a></p>
        @else
          @include('talent.partials.content-editor', ['type' => $panel['type'], 'config' => $panel['config']])
        @endif
      </section>
    @empty
      <section class="block"><p class="none">{{ __('This section has no content blocks yet.') }} <a class="link" href="{{ route('talent.profile.edit') }}#sections">{{ __('Manage your sections') }}</a></p></section>
    @endforelse
  </div>
</main>
</x-fama.layout>
