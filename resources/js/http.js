/**
 * Fama shared HTTP wrapper.
 *
 * A thin fetch() wrapper so Blade pages never reload: every interaction is Ajax.
 * It attaches the CSRF token and the headers Laravel needs to treat the request
 * as XHR/JSON, then parses Fama's single JSON envelope:
 *
 *   { success, data, message, errors, meta }
 *
 * On a non-2xx response, or a 2xx whose envelope has `success: false`, it throws
 * an {@link ApiError} carrying `status`, `message`, `errors` (the field bag) and
 * `meta`, so callers can surface validation errors inline without a reload.
 *
 * Usage:
 *   import { http, get, post } from './http';
 *   const { data } = await get('/api/talents');
 *   try { await post('/login', { email, password, role }); }
 *   catch (e) { if (e instanceof ApiError) showErrors(e.errors); }
 */

/** Error thrown for any failed request; exposes the parsed envelope fields. */
export class ApiError extends Error {
    constructor(message, { status = 0, errors = null, data = null, meta = null } = {}) {
        super(message || 'Request failed');
        this.name = 'ApiError';
        this.status = status;
        this.errors = errors;
        this.data = data;
        this.meta = meta;
    }
}

/** Read the CSRF token from the <meta name="csrf-token"> tag. */
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

/** The active locale URL prefix ('' on the default locale, e.g. '/ar' otherwise). */
function localePrefix() {
    return document.querySelector('meta[name="locale-prefix"]')?.getAttribute('content') ?? '';
}

/**
 * Prefix same-origin, root-relative request URLs with the active locale so a
 * page opened under /ar hits the /ar route and gets Arabic content back. Absolute
 * URLs, protocol-relative URLs, and already-prefixed paths are left untouched.
 */
export function localizeUrl(url) {
    const prefix = localePrefix();
    if (!prefix || typeof url !== 'string') return url;
    if (!url.startsWith('/') || url.startsWith('//')) return url;
    if (url === prefix || url.startsWith(`${prefix}/`)) return url;
    return prefix + url;
}

/**
 * Core request function.
 *
 * @param {string} url
 * @param {{ method?: string, body?: any, headers?: Record<string,string>, signal?: AbortSignal }} options
 * @returns {Promise<{ success: boolean, data: any, message: string|null, errors: object|null, meta: object|null }>}
 */
export async function http(url, { method = 'GET', body = null, headers = {}, signal } = {}) {
    const isFormData = typeof FormData !== 'undefined' && body instanceof FormData;

    const finalHeaders = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken(),
        ...headers,
    };

    // Only set JSON content type when we're serialising a plain object body.
    if (body !== null && !isFormData && !(typeof body === 'string')) {
        finalHeaders['Content-Type'] = 'application/json';
    }

    const response = await fetch(localizeUrl(url), {
        method,
        headers: finalHeaders,
        credentials: 'same-origin',
        signal,
        body: body === null || isFormData || typeof body === 'string' ? body : JSON.stringify(body),
    });

    // No-content responses (204) have no envelope.
    let payload = null;
    if (response.status !== 204) {
        try {
            payload = await response.json();
        } catch {
            payload = null;
        }
    }

    const envelope = payload ?? { success: response.ok, data: null, message: null, errors: null, meta: null };

    if (!response.ok || envelope.success === false) {
        throw new ApiError(envelope.message, {
            status: response.status,
            errors: envelope.errors ?? null,
            data: envelope.data ?? null,
            meta: envelope.meta ?? null,
        });
    }

    return envelope;
}

/** Convenience helpers. */
export const get = (url, options = {}) => http(url, { ...options, method: 'GET' });
export const post = (url, body, options = {}) => http(url, { ...options, method: 'POST', body });
export const put = (url, body, options = {}) => http(url, { ...options, method: 'PUT', body });
export const patch = (url, body, options = {}) => http(url, { ...options, method: 'PATCH', body });
export const del = (url, options = {}) => http(url, { ...options, method: 'DELETE' });

// Expose on window for inline Alpine handlers that don't import modules.
window.fama = { http, get, post, put, patch, del, ApiError, localizeUrl };
