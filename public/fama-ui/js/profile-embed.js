/**
 * The public profile in EMBED mode (?embed=1, owner only) — the onboarding's live
 * preview (ADR-AR) frames the real page at desktop width. The parent page posts:
 *   { type: 'fama-preview', draft: { name, headline, place, bio, links } } — the typed draft,
 *       written into the page's data-pv hooks with textContent (never HTML);
 *   { type: 'fama-preview-focus', sec } — which part the current onboarding screen
 *       edits ('you' | 'craft' | 'work' | 'story' | 'live'): ONE RING PER PART it edits, first scrolled in;
 *   { type: 'fama-preview-specs', skill, names, added } — a skill's specialities, rewritten in place.
 * Only same-origin messages are honoured. Links never navigate inside the frame.
 */
(function () {
    // Parts of the real page each onboarding screen edits. Each screen is a list of GROUPS;
    // every group gets its OWN ring (the union box of its visible parts), so a screen that
    // edits two places shows two rings (owner 2026-11-25). The first group is scrolled to.
    var SECTIONS = {
        you: [['.ct-av', '[data-pv="name"]', '.ct-who', '.ct-tags']],
        // skills show twice: the tags in the header AND the tab bar + its speciality chips
        craft: [['.ct-skills li'], ['#skillArea .stabs [role="tab"]', '#skillArea .specs-row li']],
        story: [['[data-pv="bio"]', '.ct-pov', '.ct-acts']], // the headline is part of "you"
        work: [['.ct-coverwrap', '.ct-cover.empty'], ['#skill-tabpanel', '#skillArea [x-ref="panel"]']],
    };

    var css = document.createElement('style');
    css.textContent = 'html{scroll-behavior:smooth}@media (prefers-reduced-motion:reduce){html{scroll-behavior:auto}.pv-ring{transition:none!important;animation:none!important}}'
        + '.pv-ring{position:absolute;z-index:50;pointer-events:none;border-radius:18px;border:3px solid #AD50D3;'
        + 'background:rgba(173,80,211,.05);box-shadow:0 0 0 6px rgba(173,80,211,.14);'
        + 'transition:top .35s cubic-bezier(.2,.9,.3,1),left .35s cubic-bezier(.2,.9,.3,1),width .35s cubic-bezier(.2,.9,.3,1),height .35s cubic-bezier(.2,.9,.3,1),opacity .2s}'
        + '.pv-ring.pulse{animation:pvPulse 1.4s cubic-bezier(.2,.9,.3,1) 1}'
        + '@keyframes pvPulse{0%{box-shadow:0 0 0 0 rgba(173,80,211,.45)}100%{box-shadow:0 0 0 22px rgba(173,80,211,0)}}'
        + 'a,button{cursor:default!important}'
        // the typing effect: the line being written glows and carries a blinking orchid caret
        + '.pv-typing{background:rgba(173,80,211,.10);border-radius:6px;box-shadow:0 0 0 4px rgba(173,80,211,.10);transition:background-color .4s,box-shadow .4s}'
        + '.pv-typing::after{content:"";display:inline-block;width:2px;height:1.05em;margin-inline-start:2px;vertical-align:-.15em;background:#AD50D3;animation:pvCaret 1s steps(1) infinite}'
        + '@keyframes pvCaret{50%{opacity:0}}'
        + '@media (prefers-reduced-motion:reduce){.pv-typing::after{animation:none}}'
        // a speciality just added: the chip pops in and glows orchid for a moment
        + '.pv-new{background:#AD50D3!important;border-color:#AD50D3!important;color:#fff!important;animation:pvPop .5s cubic-bezier(.2,.9,.3,1.4)}'
        + '@keyframes pvPop{0%{transform:scale(.6);opacity:0}100%{transform:scale(1);opacity:1}}';
    document.head.appendChild(css);

    /** The rings on screen (one per group of the current screen); reused between screens. */
    var rings = [];
    var current = null;
    function ringAt(i) {
        if (!rings[i]) {
            var r = document.createElement('div');
            r.className = 'pv-ring';
            r.style.opacity = '0';
            document.body.appendChild(r);
            rings[i] = r;
        }
        return rings[i];
    }

    /** The visible elements of one group. */
    function visible(group) {
        // every match counts, so a ring can hug a run of chips rather than their full-width row
        var out = [];
        group.forEach(function (sel) {
            document.querySelectorAll(sel).forEach(function (el) {
                if (!el.hidden && el.offsetParent !== null) out.push(el);
            });
        });
        return out;
    }

    /**
     * Size one ring per group to the union of its visible parts (document coordinates);
     * a group with nothing visible gets no ring. Returns each group's top (null if none).
     */
    function place() {
        var groups = SECTIONS[current] || [];
        var tops = [];
        groups.forEach(function (group, i) {
            var els = visible(group);
            var ring = ringAt(i);
            if (!els.length) { ring.style.opacity = '0'; tops.push(null); return; }
            var t = Infinity, l = Infinity, r = -Infinity, b = -Infinity;
            els.forEach(function (el) {
                var x = el.getBoundingClientRect();
                t = Math.min(t, x.top); l = Math.min(l, x.left); r = Math.max(r, x.right); b = Math.max(b, x.bottom);
            });
            var pad = 14;
            ring.style.top = (t + window.scrollY - pad) + 'px';
            ring.style.left = (l + window.scrollX - pad) + 'px';
            ring.style.width = (r - l + pad * 2) + 'px';
            ring.style.height = (b - t + pad * 2) + 'px';
            ring.style.opacity = '1';
            tops.push(t + window.scrollY);
        });
        for (var j = groups.length; j < rings.length; j++) rings[j].style.opacity = '0';
        return tops;
    }

    /** Replay the pulse on every visible ring. */
    function pulse() {
        rings.forEach(function (r) {
            if (r.style.opacity === '0') return;
            r.classList.remove('pulse'); void r.offsetWidth; r.classList.add('pulse');
        });
    }

    var typingT = {};
    /**
     * Put a draft value into a hook; an empty value hides an optional hook (place, bio, pov).
     * When the text actually CHANGED (someone is typing), the line glows with a blinking
     * caret for a moment and is brought into view — the profile "writes along".
     */
    function put(key, value, hideWhenEmpty) {
        document.querySelectorAll('[data-pv="' + key + '"]').forEach(function (el) {
            var next = value || '';
            var changed = el.textContent !== next;
            el.textContent = next;
            var box = key === 'place' ? el.closest('.ct-place') : key === 'pov' ? el.closest('.ct-pov') : el;
            if (hideWhenEmpty && box) box.hidden = !next;
            if (!changed || !next || !ready) return;
            el.classList.add('pv-typing');
            clearTimeout(typingT[key]);
            typingT[key] = setTimeout(function () { el.classList.remove('pv-typing'); }, 1200);
            var r = el.getBoundingClientRect();
            if (r.bottom > window.innerHeight - 40 || r.top < 0) {
                window.scrollTo({ top: Math.max(0, r.top + window.scrollY - window.innerHeight / 3) });
            }
        });
    }
    var ready = false; // the first draft after a (re)load only syncs — no glow

    /** Availability: the chip's words + yellow, the Will travel chip, the green dot. */
    function putNow(d) {
        var chip = document.querySelector('[data-pv="avail"]');
        if (chip && typeof d.available === 'boolean') {
            chip.textContent = chip.dataset.on;
            chip.classList.toggle('on', d.available);
            chip.hidden = !d.available; // closed → no tag at all
        }
        var dot = document.querySelector('[data-pv="dot"]');
        if (dot && typeof d.available === 'boolean') dot.hidden = !d.available;
        var travel = document.querySelector('[data-pv="travel"]');
        if (travel && typeof d.travel === 'boolean') travel.hidden = !d.travel;
    }

    /**
     * Rebuild the links row from the draft: [{ platform, url, label }]. Icons come from the
     * page's own <template id="pv-icons"> (the same marks the server renders); text via title only.
     */
    function putLinks(list) {
        var ul = document.querySelector('[data-pv="links"]');
        var icons = document.getElementById('pv-icons');
        if (!ul || !icons || !Array.isArray(list)) return;
        ul.textContent = '';
        list.forEach(function (l) {
            var icon = icons.content.querySelector('[data-p="' + l.platform + '"]') || icons.content.querySelector('[data-p="other"]');
            var li = document.createElement('li');
            var a = document.createElement('a');
            a.href = l.url || '#';
            a.title = l.label || '';
            a.setAttribute('aria-label', l.label || l.platform);
            if (icon) a.appendChild(icon.firstElementChild.cloneNode(true));
            li.appendChild(a);
            ul.appendChild(li);
        });
        ul.hidden = list.length === 0;
    }

    /** The skill area's Alpine component (profileTabs), when the profile has one. */
    function tabs() {
        var el = document.getElementById('skillArea');
        return el && window.Alpine ? window.Alpine.$data(el) : null;
    }

    /** Open a skill's tab (no page load — the profile's own lazy tab fetch) and wait for it. */
    function openTab(slug) {
        return new Promise(function (resolve) {
            var t = tabs();
            if (!t || !t.tabs || t.tabs.indexOf(slug) < 0) { resolve(false); return; }
            if (t.active !== slug) t.show(slug, { push: false });
            var tries = 0;
            (function wait() {
                if ((t.active === slug && !t.loading) || tries++ > 40) { resolve(t.active === slug); return; }
                setTimeout(wait, 50);
            })();
        });
    }

    /**
     * A skill's specialities changed: open its tab, rewrite the chips at the top of the
     * panel (the same markup skill-blocks renders), ring the row, scroll to it, and make
     * the one just added pop. Text via textContent only.
     */
    function putSpecs(slug, names, added) {
        openTab(slug).then(function (opened) {
            var panel = document.getElementById('skill-tabpanel') || document.querySelector('#skillArea [x-ref="panel"]');
            if (!opened && !panel) return;
            if (!panel) return;
            var row = panel.querySelector('.specs-row');
            if (!names.length) { if (row) row.remove(); current = 'craft'; place(); return; }
            if (!row) {
                row = document.createElement('div');
                row.className = 'pv-sh specs-row';
                var ul0 = document.createElement('ul');
                ul0.className = 'chips specs';
                ul0.style.cssText = 'list-style:none;padding:0;margin-bottom:0';
                row.appendChild(ul0);
                panel.insertBefore(row, panel.firstChild);
            }
            var ul = row.querySelector('ul');
            ul.textContent = '';
            var newChip = null;
            names.forEach(function (n) {
                var li = document.createElement('li');
                var chip = document.createElement('span');
                chip.className = 'chip';
                chip.textContent = n;
                if (n === added) { chip.classList.add('pv-new'); newChip = chip; }
                li.appendChild(chip);
                ul.appendChild(li);
            });
            if (newChip) setTimeout(function () { newChip.classList.remove('pv-new'); }, 1600);
            // ring both places skills show, and scroll to the one that just changed (the tab)
            current = 'craft';
            var tops = place();
            pulse();
            if (tops[1] !== null && tops[1] !== undefined) window.scrollTo({ top: Math.max(0, tops[1] - 120) });
        });
    }

    /** Swap a skill slug inside a cloned node's attributes (Alpine re-inits added nodes). */
    function reslug(el, from, to) {
        [el].concat([].slice.call(el.querySelectorAll('*'))).forEach(function (n) {
            [].slice.call(n.attributes || []).forEach(function (a) {
                if (a.value.indexOf(from) >= 0) n.setAttribute(a.name, a.value.split(from).join(to));
            });
        });
    }

    /** Ask the onboarding to reload the frame (a change this page can't apply in place). */
    function needReload() {
        if (window.parent !== window) window.parent.postMessage({ type: 'fama-preview-reload' }, window.location.origin);
    }

    /** A node from one of the page's <template>s with __SKILL__ / __NAME__ filled in. */
    function fromTpl(id, slug, name) {
        var tpl = document.getElementById(id);
        if (!tpl) return null;
        var node = tpl.content.firstElementChild.cloneNode(true);
        reslug(node, '__SKILL__', slug);
        var walk = document.createTreeWalker(node, NodeFilter.SHOW_TEXT);
        while (walk.nextNode()) { if (walk.currentNode.nodeValue.indexOf('__NAME__') >= 0) walk.currentNode.nodeValue = walk.currentNode.nodeValue.split('__NAME__').join(name); }
        return node;
    }

    /**
     * The skills changed: rewrite the header tags and the tab bar IN PLACE, in the given order
     * ([{ slug, name }]), and keep profileTabs' tabs/labels/urls in step, so a new tab lazy-loads
     * its body from the server like any other. In embed mode the skill area and the bar always
     * exist (the bar hidden while ≤1 skill), so ANY count works without a reload. `open` = the
     * tab to show (a skill just added — only once the server has it).
     */
    function putSkills(list, open) {
        var head = document.querySelector('.ct-skills');
        var area = document.getElementById('skillArea');
        var bar = document.querySelector('#skillArea .stabs [role="tablist"]');
        var t = tabs();
        if (!head || !area || !bar || !t) { needReload(); return; }

        // header tags: keep the existing ones, build the new ones from the template
        var kept = {};
        [].slice.call(head.querySelectorAll('[data-goskill]')).forEach(function (a) { kept[a.getAttribute('data-goskill')] = a.closest('li'); });
        head.textContent = '';
        list.forEach(function (s) { head.appendChild(kept[s.slug] || fromTpl('pv-skilltag-tpl', s.slug, s.name)); });
        head.hidden = list.length === 0;

        // tab bar
        var tabKept = {};
        [].slice.call(bar.querySelectorAll('[role="tab"]')).forEach(function (a) { tabKept[a.getAttribute('data-skill')] = a; });
        bar.textContent = '';
        list.forEach(function (s, i) {
            var a = tabKept[s.slug] || fromTpl('pv-tab-tpl', s.slug, s.name);
            a.setAttribute('@keydown', 'onTabKey($event, ' + i + ')');
            bar.appendChild(a);
        });
        bar.closest('.stabs').hidden = list.length <= 1;

        // the component's lists (a new skill's tab URL from the page's own pattern)
        var pattern = area.getAttribute('data-tab-url') || '';
        var labels = {}, urls = {};
        list.forEach(function (s) { labels[s.slug] = s.name; urls[s.slug] = t.urls[s.slug] || pattern.replace('__SKILL__', s.slug); });
        t.tabs = list.map(function (s) { return s.slug; });
        t.labels = labels; t.urls = urls;

        // the open tab was removed → show the first; none left → empty panel
        var panel = document.getElementById('skill-tabpanel');
        if (!list.length) { t.active = null; if (panel) panel.textContent = ''; }
        else if (t.tabs.indexOf(t.active) < 0 && !open) { t.active = null; t.show(t.tabs[0], { push: false }); }

        var after = function () {
            current = 'craft';
            var tops = place();
            pulse();
            var target = open ? tops[1] : tops[0];
            if (target !== null && target !== undefined) window.scrollTo({ top: Math.max(0, target - 120) });
        };
        if (open) openTab(open).then(after); else after();
    }

    /** Ring the screen's parts and bring the first into view ('live' = the whole page, from the top). */
    function focus(sec) {
        current = SECTIONS[sec] ? sec : null;
        var tops = current ? place() : [];
        var first = tops.filter(function (t) { return t !== null; })[0];
        if (first === undefined) {
            rings.forEach(function (r) { r.style.opacity = '0'; });
            window.scrollTo({ top: 0 });
            return;
        }
        pulse();
        window.scrollTo({ top: Math.max(0, first - 60) });
    }
    window.addEventListener('resize', function () { if (current) place(); });

    window.addEventListener('message', function (e) {
        if (e.origin !== window.location.origin || !e.data) return;
        if (e.data.type === 'fama-preview' && e.data.draft) {
            var d = e.data.draft;
            put('name', d.name);
            put('headline', d.headline);
            put('place', d.place, true);
            put('bio', d.bio, true);
            put('pov', d.pov, true);
            putNow(d);
            if (d.links) putLinks(d.links);
            ready = true;
            if (current) place(); // the text moved — keep the ring around it
        } else if (e.data.type === 'fama-preview-skills') {
            putSkills(Array.isArray(e.data.list) ? e.data.list : [], e.data.open || null);
        } else if (e.data.type === 'fama-preview-specs') {
            putSpecs(String(e.data.skill || ''), Array.isArray(e.data.names) ? e.data.names : [], e.data.added || null);
        } else if (e.data.type === 'fama-preview-focus') {
            focus(e.data.sec);
        }
    });

    // A preview, not a page to browse: links and buttons do nothing in here.
    document.addEventListener('click', function (e) {
        if (e.target.closest('a,button')) { e.preventDefault(); e.stopPropagation(); }
    }, true);

    // Tell the parent the page is ready for the draft.
    if (window.parent !== window) window.parent.postMessage({ type: 'fama-preview-ready' }, window.location.origin);
})();
