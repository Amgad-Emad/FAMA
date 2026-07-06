/**
 * Talent dashboard Alpine components. Every mutation goes through the shared
 * http.js wrapper (JSON envelope), so pages never reload; validation errors are
 * surfaced inline from `envelope.errors`, and list edits are optimistic.
 */
import { del, get, patch, post, ApiError } from './http';

/** Resolve a translation map ({en, ar}) to the current locale. */
function t(map) {
    if (!map || typeof map !== 'object') return map ?? '';
    const locale = (document.documentElement.lang || 'en').slice(0, 2);
    return map[locale] || map.en || Object.values(map)[0] || '';
}

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // --- Profile editor -----------------------------------------------------
    Alpine.data('profileEditor', (initial) => ({
        core: initial.core,
        blocks: initial.blocks,
        picker: initial.picker,
        errors: {},
        savingCore: false,
        coreSaved: false,
        dragId: null,
        heroUploading: false,
        heroUrl: initial.core.hero_image_url || null,
        t,

        async uploadHero(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.heroUploading = true;
            const body = new FormData();
            body.append('image', file);
            try {
                const { data } = await post('/talent/profile/hero', body);
                this.heroUrl = data.hero_image_url;
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            } finally {
                this.heroUploading = false;
            }
        },

        async saveCore() {
            this.errors = {};
            this.savingCore = true;
            this.coreSaved = false;
            try {
                const { data } = await patch('/talent/profile', this.core);
                this.core = { ...this.core, ...data };
                this.coreSaved = true;
                setTimeout(() => (this.coreSaved = false), 2000);
            } catch (e) {
                if (e instanceof ApiError) this.errors = e.errors || { _: [e.message] };
            } finally {
                this.savingCore = false;
            }
        },

        async addBlock(id) {
            try {
                const { data } = await post('/talent/profile/blocks', { block_type_id: id });
                this.blocks.push(data);
                await this.refreshPicker();
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            }
        },

        async removeBlock(block) {
            const prev = [...this.blocks];
            this.blocks = this.blocks.filter((b) => b.id !== block.id);
            try {
                await del(`/talent/profile/blocks/${block.id}`);
                await this.refreshPicker();
            } catch (e) {
                this.blocks = prev;
            }
        },

        async toggleVisible(block) {
            block.is_visible = !block.is_visible;
            try {
                await patch(`/talent/profile/blocks/${block.id}/visibility`, { is_visible: block.is_visible });
            } catch (e) {
                block.is_visible = !block.is_visible;
            }
        },

        async saveTitle(block) {
            try {
                await patch(`/talent/profile/blocks/${block.id}`, { title: block.title });
                block._saved = true;
                setTimeout(() => (block._saved = false), 1500);
            } catch (e) {
                /* validation ignored for the inline title */
            }
        },

        onDrop(target) {
            if (this.dragId === null || this.dragId === target.id) return;
            const from = this.blocks.findIndex((b) => b.id === this.dragId);
            const to = this.blocks.findIndex((b) => b.id === target.id);
            const [moved] = this.blocks.splice(from, 1);
            this.blocks.splice(to, 0, moved);
            this.dragId = null;
            this.persistOrder();
        },

        async persistOrder() {
            try {
                await patch('/talent/profile/blocks/reorder', { order: this.blocks.map((b) => b.id) });
            } catch (e) {
                /* keep optimistic order; a reload would resync */
            }
        },

        async refreshPicker() {
            try {
                const { data } = await get('/talent/profile/block-picker');
                this.picker = data;
            } catch (e) {
                /* ignore */
            }
        },
    }));

    // --- Professions manager ------------------------------------------------
    Alpine.data('professionsManager', (initial) => ({
        linked: initial.linked,
        available: initial.available,
        addId: '',
        dragId: null,
        t,

        async refresh(payload) {
            if (payload) {
                this.linked = payload.linked;
                this.available = payload.available;
                return;
            }
            const { data } = await get('/talent/professions/data');
            this.linked = data.linked;
            this.available = data.available;
        },

        async add() {
            if (!this.addId) return;
            try {
                const { data } = await post('/talent/professions', { talent_type_id: this.addId });
                await this.refresh(data);
                this.addId = '';
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            }
        },

        async remove(type) {
            const { data } = await del(`/talent/professions/${type.id}`);
            await this.refresh(data);
        },

        async makePrimary(type) {
            const { data } = await patch(`/talent/professions/${type.id}/primary`);
            await this.refresh(data);
        },

        onDrop(target) {
            if (this.dragId === null || this.dragId === target.id) return;
            const from = this.linked.findIndex((x) => x.id === this.dragId);
            const to = this.linked.findIndex((x) => x.id === target.id);
            const [moved] = this.linked.splice(from, 1);
            this.linked.splice(to, 0, moved);
            this.dragId = null;
            patch('/talent/professions/reorder', { order: this.linked.map((x) => x.id) }).catch(() => {});
        },
    }));

    // --- Public discovery / talent search -----------------------------------
    Alpine.data('talentSearch', (initial) => ({
        types: initial.types || [],
        equipmentCategories: initial.equipmentCategories || [],
        softwareOptions: initial.softwareOptions || [],
        filters: { type: [], availability: '', city: '', equipment: '', software: '', q: '' },
        results: [],
        meta: null,
        loading: true,
        t,

        async init() {
            await this.search();
        },

        buildQuery(page) {
            const p = new URLSearchParams();
            if (this.filters.type.length) p.set('filter[type]', this.filters.type.join(','));
            if (this.filters.availability) p.set('filter[availability]', this.filters.availability);
            if (this.filters.city) p.set('filter[city]', this.filters.city);
            if (this.filters.equipment) p.set('filter[equipment]', this.filters.equipment);
            if (this.filters.software) p.set('filter[software]', this.filters.software);
            if (this.filters.q) p.set('filter[q]', this.filters.q);
            p.set('page', page);
            return p.toString();
        },

        async search(page = 1) {
            this.loading = true;
            try {
                const { data, meta } = await get(`/discover/search?${this.buildQuery(page)}`);
                this.results = data;
                this.meta = meta;
            } finally {
                this.loading = false;
            }
        },

        toggleType(slug) {
            const i = this.filters.type.indexOf(slug);
            if (i >= 0) this.filters.type.splice(i, 1);
            else this.filters.type.push(slug);
            this.search();
        },

        reset() {
            this.filters = { type: [], availability: '', city: '', equipment: '', software: '', q: '' };
            this.search();
        },
    }));

    // --- Generic list CRUD (services, reviews, affiliations, press, content) -
    Alpine.data('crudList', (config) => ({
        items: [],
        meta: null,
        page: 1,
        loading: true,
        saving: false,
        errors: {},
        form: JSON.parse(JSON.stringify(config.blank || {})),
        fields: config.fields || [],
        media: config.media || null,
        endpoint: config.endpoint,
        dataUrl: config.dataUrl || `${config.endpoint}/data`,
        dragId: null,
        t,

        async init() {
            await this.load();
        },

        async load(page = 1) {
            this.loading = true;
            try {
                const sep = this.dataUrl.includes('?') ? '&' : '?';
                const { data, meta } = await get(`${this.dataUrl}${sep}page=${page}`);
                this.items = Array.isArray(data) ? data : data.items || [];
                this.meta = meta;
                this.page = page;
            } finally {
                this.loading = false;
            }
        },

        async create() {
            this.errors = {};
            this.saving = true;
            try {
                await post(this.endpoint, this.form);
                await this.load(this.page);
                this.form = JSON.parse(JSON.stringify(config.blank || {}));
            } catch (e) {
                if (e instanceof ApiError) this.errors = e.errors || { _: [e.message] };
            } finally {
                this.saving = false;
            }
        },

        async remove(id) {
            await del(`${this.endpoint}/${id}`);
            await this.load(this.page);
        },

        async act(id, path, body = {}) {
            await patch(`${this.endpoint}/${id}/${path}`, body);
            await this.load(this.page);
        },

        /** Upload a media file to an existing item. */
        async uploadMedia(id, file) {
            if (!file) return;
            const body = new FormData();
            body.append('file', file);
            await post(`${this.endpoint}/${id}/media`, body);
            await this.load(this.page);
        },

        /** Quick-add media items: create a blank row per file, then upload it. */
        async createAndUpload(fileList) {
            this.saving = true;
            try {
                for (const file of Array.from(fileList)) {
                    const payload = JSON.parse(JSON.stringify(config.blank || {}));
                    // Infer the media type from the dropped file (gallery items).
                    if ('media_type' in payload) {
                        payload.media_type = file.type.startsWith('video') ? 'video' : 'image';
                    }
                    const { data } = await post(this.endpoint, payload);
                    const body = new FormData();
                    body.append('file', file);
                    await post(`${this.endpoint}/${data.id}/media`, body);
                }
                await this.load(this.page);
            } catch (e) {
                if (e instanceof ApiError) this.errors = e.errors || { _: [e.message] };
            } finally {
                this.saving = false;
            }
        },

        onDrop(item) {
            if (this.dragId === null || this.dragId === item.id) return;
            const from = this.items.findIndex((x) => x.id === this.dragId);
            const to = this.items.findIndex((x) => x.id === item.id);
            const [moved] = this.items.splice(from, 1);
            this.items.splice(to, 0, moved);
            this.dragId = null;
            patch(`${this.endpoint}/reorder`, { order: this.items.map((x) => x.id) }).catch(() => {});
        },
    }));
});
