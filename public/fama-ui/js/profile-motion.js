/* Profile motion (24 Oct 2026), GSAP 3.12.5 + ScrollTrigger.
   One authored moment: the profile arrives. The featured project opens from the bottom like a curtain while the
   name and its details settle in, and numbers of 10 or more count up. After that, motion stays quiet:
     - the cover picture drifts a little as the page scrolls (scrubbed)
     - each part of the work fades up once as it reaches the screen, in small batches
     - a new skill tab slides in from the side of the tab that was picked
   Everything is visible without JavaScript or with reduced motion; GSAP only animates from a hidden start it sets itself. */
(function () {
  if (!window.gsap) return;
  if (window.ScrollTrigger) gsap.registerPlugin(ScrollTrigger);
  var EASE = 'expo.out', mm = gsap.matchMedia(), q = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  /* the parts of the work that reveal on scroll */
  var ITEMS = '.wide-sections > .h1, .blk .pv-sh, .proj, .fx-fact, .xp > li, .rec:not(.more), .wtel > li, .povq, .gal > *, .looks > *, .dig > *, .pv-card';

  function reveal(root) {
    if (!window.ScrollTrigger) return;
    var els = q(ITEMS, root).filter(function (el) { return !el.dataset.fx; });
    els.forEach(function (el) { el.dataset.fx = '1'; });
    if (!els.length) return;
    /* opacity only, never visibility: hidden, so find-in-page and links like #credits still reach them */
    gsap.set(els, { opacity: 0, y: 24 });
    ScrollTrigger.batch(els, {
      start: 'top 92%', once: true, interval: 0.08, batchMax: 6,
      onEnter: function (batch) { gsap.to(batch, { opacity: 1, y: 0, duration: 0.7, ease: EASE, stagger: 0.06, overwrite: true, clearProps: 'transform,opacity' }); }
    });
  }

  function count(dd) {
    var txt = dd.textContent, n = parseInt(txt.replace(/[^\d]/g, ''), 10);
    if (!n || n < 10) return; /* a single digit counting up reads as a flicker */
    var o = { v: 0 }, fmt = function (v) { return Math.round(v).toLocaleString('en-US'); };
    dd.style.fontVariantNumeric = 'tabular-nums';
    gsap.to(o, { v: n, duration: 0.9, ease: 'power2.out', delay: 0.25, onUpdate: function () { dd.textContent = fmt(o.v); }, onComplete: function () { dd.textContent = txt; } });
  }

  mm.add('(prefers-reduced-motion: no-preference)', function () {
    var top = document.querySelector('.ct-top');
    if (top) {
      var tl = gsap.timeline({ defaults: { ease: EASE } });
      var wrap = top.querySelector('.ct-coverwrap'), cover = top.querySelector('.ct-cover'), feat = top.querySelector('.ct-feat');
      if (wrap && cover) {
        tl.fromTo(wrap, { clipPath: 'inset(100% 0% 0% 0% round 20px)' }, { clipPath: 'inset(0% 0% 0% 0% round 20px)', duration: 1.1, clearProps: 'clipPath' }, 0)
          /* the cover has a CSS transform transition for hover; it must not fight the tween */
          .fromTo(cover, { scale: 1.08, transition: 'none' }, { scale: 1, duration: 1.4, clearProps: 'transform,transition' }, 0);
        if (feat) tl.from(feat, { y: 16, autoAlpha: 0, duration: 0.7, clearProps: 'all' }, 0.55);
      }
      var id = q('.ct-id > *', top);
      tl.from(id, { y: 18, autoAlpha: 0, duration: 0.8, stagger: 0.05, clearProps: 'all' }, 0.1);
      q('.ct-stats dd', top).forEach(count);

      /* the picture drifts as the hero scrolls away */
      if (window.ScrollTrigger && cover) {
        gsap.fromTo(cover, { backgroundPosition: '50% 40%' }, { backgroundPosition: '50% 62%', ease: 'none',
          scrollTrigger: { trigger: top, start: 'top top', end: 'bottom top', scrub: 0.6 } });
      }
    }
    reveal(document);
    if (document.fonts && document.fonts.ready && window.ScrollTrigger) document.fonts.ready.then(function () { ScrollTrigger.refresh(); });
    return function () { q('[data-fx]').forEach(function (el) { delete el.dataset.fx; }); };
  });

  /* called after a skill tab replaces the work area. dir: 1 = the tab to the right was picked, -1 = to the left */
  window.famaProfileMotion = {
    tab: function (dir) {
      if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
      var body = document.getElementById('skillBody'); if (!body) return;
      gsap.from(body, { x: 24 * (dir || 1), autoAlpha: 0, duration: 0.45, ease: 'power3.out', clearProps: 'all' });
      /* what the new tab shows is already where the reader is: no second reveal for it, only for what is below */
      q(ITEMS, body).forEach(function (el) { if (el.getBoundingClientRect().top < innerHeight) el.dataset.fx = '1'; });
      reveal(body);
      if (window.ScrollTrigger) ScrollTrigger.refresh();
    }
  };
})();
