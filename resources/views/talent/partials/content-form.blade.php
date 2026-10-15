{{-- The add / edit form of one content block (fama-ui profile-content .form).
     Alpine context: `contentBlock` (fields, form, errors, save, cancel, remove). --}}
<form class="form" novalidate @submit.prevent="save()">
  <h3><span x-text="editing === 'new' ? @js(__('Add')) : @js(__('Edit'))"></span> · <span x-text="editing === 'new' ? label : (current?.label || label)"></span></h3>
  <div class="grid">
    <template x-for="field in fields" :key="field.name">
      <label class="f" :class="{ bad: err(field.name), full: isFull(field) }">
        <span><span x-text="fieldLabel(field)"></span><small x-text="required(field) ? @js(__('Required')) : @js(__('Optional'))"></small></span>
        <template x-if="field.kind === 'translatable' && (field.rich || field.name === 'notes')">
          <textarea class="ta" x-model="form[field.name].en"></textarea>
        </template>
        <template x-if="field.kind === 'translatable' && !field.rich && field.name !== 'notes'">
          <input class="in" x-model="form[field.name].en" :required="required(field)">
        </template>
        <template x-if="field.kind === 'select'">
          <select class="sel" x-model="form[field.name]">
            <template x-for="opt in field.options" :key="opt"><option :value="opt" x-text="optLabel(opt)" :selected="form[field.name] === opt"></option></template>
          </select>
        </template>
        <template x-if="['text', 'url', 'number', 'date'].includes(field.kind)">
          <input class="in" :type="field.kind === 'number' ? 'number' : (field.kind === 'date' ? 'date' : (field.kind === 'url' ? 'url' : 'text'))"
                 x-model="form[field.name]" :dir="field.kind === 'url' ? 'ltr' : null" :placeholder="field.kind === 'url' ? 'https://' : ''" :required="required(field)">
        </template>
        <p class="err" x-show="err(field.name)" x-text="err(field.name)"></p>
      </label>
    </template>
    {{-- Media goes onto an existing item (medialibrary), so upload shows when editing. --}}
    <template x-if="media && editing !== 'new' && current">
      <div class="f full">
        <span><span>{{ __('Upload') }}</span><small>{{ __('Optional') }}</small></span>
        <label class="btn btn-2" style="cursor:pointer"><svg class="icon" aria-hidden="true"><use href="#i-image"/></svg>{{ __('Upload') }}
          <input type="file" accept="image/*,video/*" hidden @change="replaceMedia(current, $event.target.files[0]); $event.target.value = ''"></label>
      </div>
    </template>
  </div>
  <p class="err" x-show="errorText && !Object.keys(errors).length" x-text="errorText"></p>
  <div class="acts">
    <template x-if="editing !== 'new' && current">
      <button type="button" class="btn btn-text" style="color:var(--rose-fg)" @click="remove(current)">{{ __('Delete') }}</button>
    </template>
    <button type="button" class="btn btn-2" @click="cancel()">{{ __('Cancel') }}</button>
    <button type="submit" class="btn" :disabled="saving">{{ __('Save') }}</button>
  </div>
</form>
