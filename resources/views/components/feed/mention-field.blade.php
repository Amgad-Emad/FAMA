{{-- The mention-aware text field (5U, repaired in 5AH). The suggestion list
     is PORTALLED to <body> and positioned from the field's rect — inside a
     frame (overflow:hidden) or the composer modal (overflow-y:auto) an
     absolutely-positioned dropdown gets clipped, which is why 5U's list never
     appeared. Under 640px it becomes a full-width sheet above the keyboard.
     Binds to the parent via x-modelable (use x-model="draft"). --}}
@props([
    'placeholder' => '',
    'rows' => 1,
    'maxRows' => 6,
    'fieldClass' => 't-comment',
])

<div x-data="mentionField({ maxRows: {{ $maxRows }} })" x-modelable="value" {{ $attributes }} class="relative w-full">
    <textarea x-ref="field" x-model="value" rows="{{ $rows }}" dir="auto"
              class="well well--grow {{ $fieldClass }} w-full p-3"
              placeholder="{{ $placeholder }}"
              role="combobox" aria-autocomplete="list" aria-haspopup="listbox"
              :aria-expanded="open ? 'true' : 'false'"
              :aria-controls="listId"
              :aria-activedescendant="open ? activeOptionId() : null"
              @input="onInput($event)"
              @keydown="onKeydown($event)"
              @blur="onBlur()"></textarea>

    {{-- The suggestion list — portalled so no ancestor can clip it (5AH). --}}
    <template x-teleport="body">
        <div x-show="open" x-cloak data-surface="daylight"
             class="mention-pop frame-shadow" :style="popStyle"
             role="listbox" :id="listId" aria-label="{{ __('Mention suggestions') }}">
            <div class="frame max-h-64 overflow-y-auto p-1">
                <p x-show="loading" class="t-meta px-3 py-2">{{ __('LOOKING…') }}</p>
                <p x-show="!loading && flat().length === 0" class="t-meta px-3 py-2">{{ __('No one found under that name.') }}</p>

                <template x-for="(group, groupName) in results" :key="groupName">
                    <div x-show="group.length > 0">
                        <p class="t-meta px-3 pt-2" x-text="groupName === 'talents' ? '{{ __('TALENTS') }}' : '{{ __('BRANDS') }}'"></p>
                        <template x-for="actor in group" :key="actor.type + '-' + actor.id">
                            <button type="button" role="option"
                                    :id="optionId(actor)"
                                    :aria-selected="isActive(actor) ? 'true' : 'false'"
                                    class="flex w-full items-center gap-2 rounded px-3 py-2 text-start hover:bg-ground-sunk"
                                    :class="{ 'bg-ground-sunk': isActive(actor) }"
                                    @mousedown.prevent="pick(actor)">
                                <img x-show="actor.avatar_url" :src="actor.avatar_url" alt="" class="h-6 w-6 rounded-full object-cover">
                                <span x-show="!actor.avatar_url" class="flex h-6 w-6 items-center justify-center rounded-full bg-ground-sunk t-meta" x-text="(actor.display_name ?? '?').slice(0, 1)"></span>
                                <span class="t-body min-w-0 truncate" x-text="actor.display_name"></span>
                                <bdi class="comment-handle" x-text="'@' + actor.username"></bdi>
                                <span class="comment-badge ms-auto"
                                      x-text="actor.type === 'brand' ? '{{ __('BRAND') }}' : '{{ __('TALENT') }}'"></span>
                            </button>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </template>

    {{-- Result count for screen readers --}}
    <p class="sr-only" aria-live="polite" x-text="announcement"></p>
</div>
