/**
 * Shared helpers for the fama-ui feed components (ui/feed*.js).
 */
import { ApiError } from '../http';

/** A runtime string from the Blade bridge (window.famaLang), with a fallback. */
export function L(key, fallback = '') {
    return window.famaLang?.[key] ?? fallback;
}

/** Replace :name placeholders in a string. */
export function fmt(str, params = {}) {
    return Object.entries(params).reduce((s, [k, v]) => s.replaceAll(':' + k, String(v)), str ?? '');
}

/** Two-letter initials from a display name (the prototype's avatar fallback). */
export function initials(name) {
    return String(name ?? '').trim().split(/\s+/).filter(Boolean).slice(0, 2)
        .map((w) => w[0].toUpperCase()).join('');
}

/** Compact relative time: now · 12m · 3h · 1d · 2w. */
export function ago(iso) {
    if (window.famaAgo) return window.famaAgo(iso);
    if (!iso) return '';
    const s = Math.max(0, (Date.now() - new Date(iso).getTime()) / 1000);
    if (s < 60) return 'now';
    if (s < 3600) return Math.floor(s / 60) + 'm';
    if (s < 86400) return Math.floor(s / 3600) + 'h';
    if (s < 604800) return Math.floor(s / 86400) + 'd';
    return Math.floor(s / 604800) + 'w';
}

/** A feed actor's public profile URL (talent /{slug}, brand /brands/{slug}). */
export function profileUrl(actor) {
    if (!actor?.username) return '#';
    return window.fama.localizeUrl(actor.type === 'brand' ? `/brands/${actor.username}` : `/${actor.username}`);
}

/**
 * Show the fama-ui toast (bottom pill, optional Undo).
 *
 * WATCH OUT: the shared toast markup binds x-show="$store.famaToast.undo", and
 * Alpine CALLS a function an expression evaluates to — so a bare undo callback
 * would run the moment the toast renders. The wrapper only acts when invoked
 * by runUndo() (which clears store.undo first); during x-show evaluation it
 * just returns true so the Undo button shows.
 */
export function toast(message, undo = null) {
    const store = window.Alpine?.store('famaToast');
    const guarded = undo ? () => {
        if (store && store.undo !== null) return true;
        undo();
        return true;
    } : null;
    window.famaToast?.(message, guarded);
}

/**
 * The guest gate: a 401 from a write means the action was captured as a
 * pending intent server-side — open the centred sign-in prompt for `verb`
 * (it replays after login). Returns true when it handled the error.
 */
/**
 * Visitor-first guard for action buttons. A signed-out viewer must NOT see the
 * button flip (e.g. "Following") before the sign-in dialog: the request is still
 * sent so the server's 401 captures the intent (replayed once after login), then
 * the dialog opens — with the button untouched. Returns true when it handled a
 * visitor (the caller stops); false for a signed-in viewer (carry on optimistically).
 */
export async function asVisitor(verb, request) {
    if (window.famaStaff) {
        // Signed-in admin: read-only on the feed — say so, send nothing.
        window.famaToast?.(window.famaStaffNote);
        return true;
    }
    if (window.famaSignedIn !== false) return false;
    try {
        await request();
    } catch (error) {
        if (!gate(error, verb)) console.warn(`${verb}: request failed`, error);
        return true;
    }
    window.famaSignIn?.(verb);
    return true;
}

export function gate(error, verb) {
    if (error instanceof ApiError && error.status === 401) {
        window.famaSignIn?.(verb);
        return true;
    }
    return false;
}

/** Platform display names for link chips (feed provider keys). */
const PLATFORMS = {
    instagram: 'Instagram', tiktok: 'TikTok', youtube: 'YouTube', behance: 'Behance', vimeo: 'Vimeo',
    x: 'X', linkedin: 'LinkedIn', pinterest: 'Pinterest', dribbble: 'Dribbble', spotify: 'Spotify',
    soundcloud: 'SoundCloud', facebook: 'Facebook', snapchat: 'Snapchat',
};

/** The chip label the prototype shows: the platform name, else the host. */
export function platformName(provider, url) {
    if (PLATFORMS[provider]) return PLATFORMS[provider];
    try { return new URL(url).hostname.replace(/^www\./, ''); } catch { return url; }
}

/** Parse an HTML string into its top-level nodes. */
export function nodesFrom(html) {
    const holder = document.createElement('template');
    holder.innerHTML = String(html ?? '').trim();
    return [...holder.content.childNodes];
}
