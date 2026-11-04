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
