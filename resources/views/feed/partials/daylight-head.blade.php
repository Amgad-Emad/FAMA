{{-- Daylight fonts (Prompt 5L) — loaded on feed pages only. Fraunces variable
     ships with the SOFT + WONK axes enabled; Amiri carries Arabic display;
     Instrument Sans / IBM Plex Sans Arabic carry body & UI; Geist Mono is the
     utility face. display=swap throughout. Self-hosting these is a deploy-time
     swap (docs/design-system.md). --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,SOFT,WONK,wght@9..144,0..100,0..1,300..700;1,9..144,0..100,0..1,300..700&family=Instrument+Sans:wght@400;500;600&family=Geist+Mono:wght@400;500&family=Amiri:wght@400;700&family=IBM+Plex+Sans+Arabic:wght@400;500;600&display=swap" rel="stylesheet">

{{-- The feed's JS string bridge — feed.js reads window.famaLang for every
     runtime-composed string (toasts, hints, a11y announcements) so they
     localize like Blade copy. Keys mirror the famaLang?.x reads in feed.js. --}}
<script>
    window.famaLang = {
        alreadyReported: @js(__('You already reported this.')),
        badMediaType: @js(__('That file type is not supported.')),
        closeComposerBody: @js(__('Your draft is kept for this page visit.')),
        closeComposerTitle: @js(__('Close the composer?')),
        closeLabel: @js(__('CLOSE')),
        commentPosted: @js(__('Comment posted.')),
        commentUpdated: @js(__('Comment updated.')),
        dismissed: @js(__('Suggestion dismissed')),
        dismissedUndo: @js(__('Dismissed.')),
        followedUndo: @js(__('Followed.')),
        hintEmpty: @js(__('Write something or add media')),
        hintPickProject: @js(__('Pick a Project to share')),
        hintTooLong: @js(__('The body is over the limit')),
        igCopied: @js(__('Link copied — paste it into your Instagram story, bio, or DM.')),
        igManual: @js(__('Copy this post’s link, then paste it into Instagram.')),
        openInstagram: @js(__('Open Instagram')),
        notifyOn: @js(__('You will be notified when :name posts an opportunity.')),
        notifyOff: @js(__('Opportunity alerts off for :name.')),
        mediaTooBig: @js(__('That file is too large.')),
        mentionResults: @js(__('suggestions — arrow keys to browse, Enter to pick')),
        noMentions: @js(__('No one found under that name.')),
        nowFollowing: @js(__('now following')),
        publishFailed: @js(__('Could not publish — try again.')),
        savedToast: @js(__('Saved.')),
        signInLike: @js(__('Sign in to like this')),
        signInReport: @js(__('Sign in to report this')),
        signInSave: @js(__('Sign in to save this')),
        tooManyMedia: @js(__('That is more media than a post can carry.')),
        unliked: @js(__('Like removed')),
        unsaved: @js(__('Removed from saved')),
        unsavedToast: @js(__('Removed from saved.')),
        viewSaved: @js(__('VIEW SAVED')),
        with: @js(__('WITH')),
    };
</script>
