{{-- One content block's items on the profile-content page (fama-ui list /
     media blocks). Alpine context: `contentBlock` on the enclosing section.
     Gallery = a media grid with an upload tile (select a tile to edit it);
     every other type = a reorderable list, edited in place. --}}
@if ($type === 'gallery')
  <div class="media" data-list="gallery">
    <template x-for="item in items" :key="item.id">
      <button type="button" class="tile" draggable="true" :class="{ dragging: dragId === item.id }"
              @dragstart="dragId = item.id" @dragend="dragId = null" @dragover.prevent @drop.prevent="onDrop(item)"
              :aria-pressed="(selTile === item.id).toString()" :aria-label="item.label || label" @click="pickTile(item)">
        <template x-if="item.thumb"><img :src="item.thumb" alt=""></template>
        <span class="ic" x-show="!item.thumb"><svg class="icon" aria-hidden="true"><use :href="(item.values?.media_type || 'image') === 'image' ? '#i-image' : '#i-play'" href="#i-image"/></svg></span>
        <span class="kind" x-text="optLabel(item.values?.media_type || 'image')"></span>
        <span class="cap" x-text="item.label || '—'"></span>
      </button>
    </template>
    <label class="drop"><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg><span x-text="saving ? @js(__('Uploading…')) : @js(__('Add photos or video'))">{{ __('Add photos or video') }}</span><small>{{ __('JPG, PNG, WebP, MP4') }}</small>
      <input type="file" multiple accept="image/*,video/*" hidden @change="upload($event.target.files); $event.target.value = ''"></label>
  </div>
  <p x-show="editing === null" style="margin-top:var(--s3)"><button type="button" class="btn btn-text" style="padding-inline:0" @click="startAdd({ media_type: 'embed' })">{{ __('Or add an embed link') }}</button></p>
  <template x-if="editing !== null">
    <div>@include('talent.partials.content-form')</div>
  </template>
@else
  <ul class="items" data-list="{{ $type }}" x-show="items.length">
    <template x-for="item in items" :key="item.id">
      <li :class="{ item: editing !== item.id, dragging: dragId === item.id }" :draggable="editing === item.id ? 'false' : 'true'"
          @dragstart="dragId = item.id" @dragend="dragId = null" @dragover.prevent @drop.prevent="onDrop(item)">
        <template x-if="editing !== item.id">
          <span style="display:contents">
            <span class="grip" :title="@js(__('Drag to reorder :name')).replace(':name', item.label)" aria-hidden="true"><svg class="icon"><use href="#i-grip"/></svg></span>
            <span><b x-text="item.label || '—'"></b><span class="meta" x-text="subOf(item)"></span></span>
            <span class="acts">
              <button type="button" class="iconbtn" @click="startEdit(item)" :aria-label="@js(__('Edit :name')).replace(':name', item.label || label)"><svg class="icon" aria-hidden="true"><use href="#i-edit"/></svg></button>
              <button type="button" class="iconbtn del" @click="remove(item)" :aria-label="@js(__('Delete :name')).replace(':name', item.label || label)"><svg class="icon" aria-hidden="true"><use href="#i-trash"/></svg></button>
            </span>
          </span>
        </template>
        <template x-if="editing === item.id">
          <div>@include('talent.partials.content-form')</div>
        </template>
      </li>
    </template>
  </ul>
  <p class="none" x-show="!loading && !items.length && editing !== 'new'">{{ __('Nothing here yet.') }}</p>
  <template x-if="editing === 'new'">
    <div>@include('talent.partials.content-form')</div>
  </template>
@endif
