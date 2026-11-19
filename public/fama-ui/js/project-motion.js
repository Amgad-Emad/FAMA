/* Project page motion (fama-ui project.html, 16–24 Oct 2026), GSAP 3.12.5 + ScrollTrigger — ported verbatim
   from the prototype's inline motion script; only the prototype's fake-data guard is gone.
   It follows the reading; with reduced motion, or no network (no GSAP), the page simply shows. */
(function () {
  /**
   * Run the motion once the page and GSAP are both there.
   */
  function start() {
    if (!document.querySelector('.ed .cover') || !window.gsap || !window.ScrollTrigger || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    gsap.registerPlugin(ScrollTrigger);
    var $ = function (q, r) { return [].slice.call((r || document).querySelectorAll(q)); }, one = function (q) { return document.querySelector(q); };
    /* every picture sits in a frame so it can drift inside it */
    $('.story figure:not(.cs-fig):not(.video):not([data-nolb]) .ph-img').forEach(function (el) {
      var f = document.createElement('div'); f.className = 'frame'; f.style.aspectRatio = getComputedStyle(el).aspectRatio;
      el.replaceWith(f); f.appendChild(el); el.style.aspectRatio = 'auto'; el.style.height = '118%'; el.style.marginTop = '-9%';
    });
    /** Wrap each word of an element in a span so it can light up on its own. */
    var words = function (el) { el.innerHTML = el.innerHTML.replace(/(<[^>]+>)|([^\s<]+)/g, function (m, tag, w) { return tag ? tag : '<span class="w">' + w + '</span>'; }); return $('.w', el); };
    var h1 = one('.cover h1'); h1.innerHTML = '<span class="ln"><span>' + h1.innerHTML + '</span></span>';
    gsap.timeline({ defaults: { ease: 'power3.out' } })
      .from('.cover .ph-img', { scale: 1.15, duration: 1.6, ease: 'power2.out' }, 0)
      .from('.cover h1 .ln>span', { yPercent: 110, duration: 1 }, 0.2)
      .from(['.cover .kick', '.cover .by', '.cover .acts'], { y: 16, opacity: 0, duration: 0.7, stagger: 0.08 }, 0.55);
    gsap.to('.cover .ph-img', { yPercent: 18, ease: 'none', scrollTrigger: { trigger: '.cover', start: 'top top', end: 'bottom top', scrub: true } });
    gsap.to('.cover .cap', { y: -60, opacity: 0, ease: 'none', scrollTrigger: { trigger: '.cover', start: '30% top', end: 'bottom top', scrub: true } });
    if (one('.colo')) gsap.from('.colo>div', { y: 20, opacity: 0, duration: 0.6, stagger: 0.08, ease: 'power2.out', scrollTrigger: { trigger: '.colo', start: 'top 90%' } });
    if (one('.s-intro')) gsap.from('.s-facts>div', { y: 16, opacity: 0, duration: 0.5, stagger: 0.08, scrollTrigger: { trigger: '.s-intro', start: 'top 85%' } });
    var lead = one('.story .lead'); if (lead) gsap.fromTo(words(lead), { opacity: 0.15 }, { opacity: 1, stagger: 0.05, ease: 'none', scrollTrigger: { trigger: lead, start: 'top 80%', end: 'bottom 45%', scrub: true } });
    $('.ch-h').forEach(function (h) {
      gsap.timeline({ scrollTrigger: { trigger: h, start: 'top 85%' } })
        .fromTo(h, { backgroundSize: '0% 1px' }, { backgroundSize: '100% 1px', duration: 1.1, ease: 'power3.inOut' })
        .fromTo(h.querySelector('h2'), { yPercent: 60, opacity: 0 }, { yPercent: 0, opacity: 1, duration: 0.8, ease: 'power3.out' }, '<.25')
        .from(h.querySelector('span'), { opacity: 0, y: 8, duration: 0.5 }, '<');
    });
    $('.story .txt').forEach(function (el) { gsap.from(el, { y: 24, opacity: 0, duration: 0.8, ease: 'power2.out', scrollTrigger: { trigger: el, start: 'top 88%' } }); });
    $('.frame').forEach(function (f) {
      gsap.fromTo(f, { clipPath: 'inset(18% 0% 0% 0% round 12px)' }, { clipPath: 'inset(0% 0% 0% 0% round 12px)', ease: 'power2.out', duration: 1.1, scrollTrigger: { trigger: f, start: 'top 85%' } });
      gsap.fromTo(f.firstElementChild, { yPercent: -6 }, { yPercent: 6, ease: 'none', scrollTrigger: { trigger: f, start: 'top bottom', end: 'bottom top', scrub: true } });
    });
    $('.pair figure:last-child').forEach(function (f) { gsap.fromTo(f, { y: 40 }, { y: -40, ease: 'none', scrollTrigger: { trigger: f.parentElement, start: 'top bottom', end: 'bottom top', scrub: true } }); });
    /* the "Cases" gallery: pinned stage, the column slides up through it, the list and counter follow (ADR-AN) */
    $('[data-cases]').forEach(function (cs) {
      var mm = gsap.matchMedia(), start = +cs.dataset.start;
      mm.add('(min-width:761px)', function () {
        cs.classList.add('live');
        var stage = cs.querySelector('.cs-stage'), col = cs.querySelector('.cs-col'), figs = $('.cs-fig', cs), lis = $('.cs-list li', cs), num = cs.querySelector('.cs-count b');
        if (!figs.length) return function () { cs.classList.remove('live'); };
        var cur = -1;
        var active = function () {
          var r = stage.getBoundingClientRect(), mid = r.top + r.height / 2, best = 0, bd = 1e9;
          figs.forEach(function (f, i) { var b = f.getBoundingClientRect(), d = Math.abs(b.top + b.height / 2 - mid); if (d < bd) { bd = d; best = i; } });
          if (best !== cur) {
            cur = best; lis.forEach(function (l, i) { l.classList.toggle('on', i === best); });
            num.textContent = String(best + start).padStart(2, '0');
            gsap.fromTo(num, { yPercent: 40, opacity: 0 }, { yPercent: 0, opacity: 1, duration: 0.35, overwrite: true });
          }
        };
        var ctr = function (f) { return f.offsetTop + f.offsetHeight / 2; },
          y0 = function () { return stage.offsetHeight * 0.12 - figs[0].offsetTop; },
          y1 = function () { return stage.offsetHeight / 2 - ctr(figs[figs.length - 1]) - stage.offsetHeight * 0.1; };
        gsap.fromTo(col, { y: y0 }, { y: y1, ease: 'none', onUpdate: active, scrollTrigger: { trigger: stage, start: 'top 72px', end: function () { return '+=' + (y0() - y1()) * 1.1; }, pin: true, scrub: 0.6, invalidateOnRefresh: true, onUpdate: active, onRefresh: active } });
        gsap.from(cs.querySelector('.cs-title'), { yPercent: 30, opacity: 0, duration: 1, ease: 'power3.out', scrollTrigger: { trigger: stage, start: 'top 70%' } });
        return function () { cs.classList.remove('live'); };
      });
    });
    /* a quote lights up word by word as it is read; who said it follows */
    $('.quote').forEach(function (q) {
      var c = q.querySelector('cite'); if (c) c.remove();
      gsap.fromTo(words(q), { opacity: 0.12 }, { opacity: 1, stagger: 0.08, ease: 'none', scrollTrigger: { trigger: q, start: 'top 75%', end: 'bottom 50%', scrub: true } });
      if (c) { q.appendChild(c); gsap.from(c, { opacity: 0, y: 10, duration: 0.6, scrollTrigger: { trigger: q, start: 'bottom 60%' } }); }
    });
    $('.res').forEach(function (res) {
      var st = { trigger: res, start: 'top 80%' };
      $(':scope>div', res).forEach(function (d, i) {
        var b = d.querySelector('b'), node = [].slice.call(b.childNodes).find(function (n) { return n.nodeType === 3 && n.textContent.trim(); }); if (!node) return;
        var m = node.textContent.trim().match(/^([+\-−]?)([\d.]+)(.*)$/);
        gsap.from(d, { y: 30, opacity: 0, duration: 0.7, delay: i * 0.12, ease: 'power2.out', scrollTrigger: st }); if (!m) return;
        var end = parseFloat(m[2]), dec = (m[2].split('.')[1] || '').length, o = { v: 0 };
        gsap.to(o, { v: end, duration: 1.6, delay: i * 0.12, ease: 'power2.out', scrollTrigger: st, onUpdate: function () { node.textContent = m[1] + o.v.toFixed(dec) + m[3]; } });
      });
      gsap.from($('.bar i', res), { width: 0, duration: 1.4, delay: 0.3, ease: 'power2.out', scrollTrigger: st });
      gsap.from($('.ar', res), { scale: 0, rotate: -90, duration: 0.6, delay: 0.5, ease: 'back.out(2)', scrollTrigger: st });
    });
    if (one('.cred')) gsap.from('.cred li', { x: -16, opacity: 0, duration: 0.5, stagger: 0.08, ease: 'power2.out', scrollTrigger: { trigger: '.cred', start: 'top 85%' } });
    if (one('.next')) {
      gsap.fromTo('.next .ph-img', { scale: 1.2 }, { scale: 1, ease: 'none', scrollTrigger: { trigger: '.next', start: 'top bottom', end: 'bottom bottom', scrub: true } });
      gsap.from('.next .cap>*', { y: 40, opacity: 0, duration: 0.9, stagger: 0.1, ease: 'power3.out', scrollTrigger: { trigger: '.next', start: 'top 60%' } });
    }
    if (one('.more')) gsap.from('.more a', { y: 30, opacity: 0, duration: 0.6, stagger: 0.1, ease: 'power2.out', scrollTrigger: { trigger: '.more', start: 'top 90%' } });
    addEventListener('load', function () { ScrollTrigger.refresh(); });
    if (document.fonts) document.fonts.ready.then(function () { ScrollTrigger.refresh(); });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
})();
