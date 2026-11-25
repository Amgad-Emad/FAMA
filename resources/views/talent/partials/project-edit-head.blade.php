{{--
    The project editor's bar (fama-ui project-editor.js, 30 Oct–1 Nov 2026): the way back, the project's name
    with one line of state under it (Draft / Published, then the save state), Settings, Preview and the main
    button. Rendered twice by talent/project-edit (only one at a time): docked at the top of the page panel on
    wide screens, or inside the site header (which steps aside) on narrow ones. Scope: famaProjectEdit.
--}}
<div class="pe-head">
    {{-- Opened from onboarding (ADR-AR, ?from=welcome): the way back is the Work screen. --}}
    @if ($fromWelcome ?? false)
    <a class="back" href="{{ route('talent.onboarding', ['step' => 'work']) }}" aria-label="{{ __('Back to setup') }}"><svg class="pe-i" aria-hidden="true"><use href="#e-left"/></svg><span class="lbl">{{ __('Setup') }}</span></a>
    @else
    <a class="back" href="{{ route('talent.projects') }}" aria-label="{{ __('Back to projects') }}"><svg class="pe-i" aria-hidden="true"><use href="#e-left"/></svg><span class="lbl">{{ __('Projects') }}</span></a>
    @endif
    <div class="pe-mid">
        <b class="pe-name" x-text="displayName">{{ $initialName }}</b>
        <span class="pe-meta">
            <span class="pill" :class="id ? 'p-live' : 'p-off'" x-text="id ? strings.published : strings.draft">{{ $isNew ? __('Draft') : __('Published') }}</span>
            <span class="saved" role="status"><i :class="savedDot"></i><span x-text="savedText"></span></span>
        </span>
    </div>
    <button class="btn btn-2" type="button" @click="openSettings($el)" aria-haspopup="dialog" aria-label="{{ __('Settings') }}"><svg class="pe-i" aria-hidden="true"><use href="#e-sliders"/></svg><span class="lbl">{{ __('Settings') }}</span></button>
    <a class="btn btn-2" :href="previewUrl" x-show="previewUrl" x-cloak target="_blank" rel="noopener" aria-label="{{ __('Preview') }}"><svg class="pe-i" aria-hidden="true"><use href="#e-eye"/></svg><span class="lbl">{{ __('Preview') }}</span></a>
    <button class="btn" type="button" data-publish @click="publish($el)" :disabled="publishing" x-text="id ? strings.update : strings.publish">{{ $isNew ? __('Publish') : __('Update') }}</button>
</div>
