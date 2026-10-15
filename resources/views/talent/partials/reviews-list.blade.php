{{-- The talent's reviews — a READ-ONLY list with a star filter (approve/reject
     live in the admin queue). Shared by the Reviews page and the Reviews block
     on the profile-content page: ONE reviews list (fama-ui .filters/.revs). --}}
<div x-data="crudList({ endpoint: '/talent/reviews' })">
  <div class="filters" role="group" aria-label="{{ __('Filter by rating') }}">
    <button type="button" @click="setQuery('')" :aria-pressed="(activeQuery === '').toString()">{{ __('All') }}</button>
    @foreach ([5, 4, 3, 2, 1] as $stars)
      <button type="button" @click="setQuery('?rating={{ $stars }}')" :aria-pressed="(activeQuery === '?rating={{ $stars }}').toString()">{{ $stars }}★</button>
    @endforeach
  </div>
  <ul class="revs" x-show="items.length" :style="loading ? 'opacity:.55' : ''">
    <template x-for="r in items" :key="r.id">
      <li class="rev">
        <div class="top">
          <span class="stars" :aria-label="r.rating + '/5'" x-html="'★'.repeat(r.rating) + '<span>' + '★'.repeat(5 - r.rating) + '</span>'"></span>
          <span class="pill" :class="r.status === 'approved' ? 'p-live' : (r.status === 'rejected' ? 'p-bad' : 'p-off')" x-text="$statusLabel(r.status)"></span>
        </div>
        <q x-text="r.body"></q>
        <p class="who" x-text="[r.reviewer_name, r.reviewer_company].filter(Boolean).join(' · ')"></p>
      </li>
    </template>
  </ul>
  <p class="none" x-show="!loading && !items.length" x-cloak>{{ __('No reviews at this rating yet.') }}</p>
  <template x-if="meta?.pagination && meta.pagination.last_page > 1">
  <div class="filters">
    <button type="button" @click="load(meta.pagination.current_page - 1)" :disabled="meta.pagination.current_page <= 1" aria-label="{{ __('Previous') }}">‹</button>
    <span class="meta" style="align-self:center" x-text="meta.pagination.current_page + ' / ' + meta.pagination.last_page"></span>
    <button type="button" @click="load(meta.pagination.current_page + 1)" :disabled="meta.pagination.current_page >= meta.pagination.last_page" aria-label="{{ __('Next') }}">›</button>
  </div>
  </template>
</div>
