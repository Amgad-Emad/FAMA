/* FAMA — the star photo on page banners (verbatim port of shell.js window.famaStar).
   Any <section class="fx-hero" data-fx-star="…jpg" data-fx-focus="feed"> gets the photo
   clipped to the brand star on the right. The star fills the banner's height and is cut
   flat at the top and bottom; on a narrow banner it shrinks so it never covers the text. */
(function () {
  'use strict';
  var PTS = '314.63 635.59 16.74 747.68 217.29 533.15 86.97 370.29 403.26 356.02 701.63 87.21 636.51 355.76 760 356.91 574.68 534.04 422.33 756.79',
      X0 = 16.74, Y0 = 87.21, VW = 743.26, VH = 669.58,
      BAND = 2.55, GAP = 26, TEXT = 340, SPACE = 32, MOB = 342,
      FOCUS = { feed: [.52, .45], activity: [.5, .45], notifications: [.55, .5], projects: [.42, .5], credits: [.5, .35], 'profile-structure': [.55, .4] },
      NS = 'http://www.w3.org/2000/svg', n = 0;

  function star(sec, hb, src, name, minText) {
    var f = FOCUS[name] || [.5, .5], id = 'fxstar' + (++n);
    sec.style.backgroundImage = 'none';
    var svg = document.createElementNS(NS, 'svg');
    svg.setAttribute('class', 'fx-star'); svg.setAttribute('aria-hidden', 'true');
    svg.setAttribute('viewBox', X0 + ' ' + Y0 + ' ' + VW + ' ' + VH); svg.setAttribute('preserveAspectRatio', 'xMidYMid slice');
    svg.innerHTML = '<defs><clipPath id="' + id + '"><polygon points="' + PTS + '"/></clipPath></defs>' +
      /* a clipping mask: the photo always covers the whole star (slice), never stretched */
      '<image href="' + src + '" preserveAspectRatio="xMidYMid slice" clip-path="url(#' + id + ')"/>' +
      '<polygon points="' + PTS + '" fill="none" stroke="#FFEB73" stroke-width="3" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>';
    sec.appendChild(svg);
    var img = svg.querySelector('image');
    function place(w, h) {
      svg.style.width = w + 'px'; svg.style.height = h + 'px';
      var k = Math.max(w / VW, h / VH), vw = w / k, vh = h / k, vx = X0 + (VW - vw) / 2, vy = Y0 + (VH - vh) / 2,
          s = Math.max(vw, vh) * 1.02, x = vx + vw / 2 - s * f[0], y = vy + vh / 2 - s * f[1];
      x = Math.min(vx, Math.max(vx + vw - s, x)); y = Math.min(vy, Math.max(vy + vh - s, y));
      img.setAttribute('x', x); img.setAttribute('y', y); img.setAttribute('width', s); img.setAttribute('height', s);
    }
    function fit() {
      var W, H, w;
      /* 23 Oct 2026 (live prototype): on a phone the star keeps its own shape — big, running off the top
         right corner on purpose (see .fx-star in shell.css) — instead of being squeezed into a strip. */
      if (window.innerWidth <= 760) { hb.style.paddingRight = ''; place(MOB * VW / VH, MOB); return; }
      for (var i = 0; i < 4; i++) {
        W = sec.offsetWidth; H = sec.offsetHeight;
        w = Math.min(H * BAND, W - parseFloat(getComputedStyle(hb).paddingLeft) - minText - SPACE - GAP);
        hb.style.paddingRight = (w + SPACE + GAP) + 'px';
      }
      place(w, sec.offsetHeight);
    }
    requestAnimationFrame(fit); setTimeout(fit, 250); window.addEventListener('resize', fit);
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(fit);
    var queued = false;
    if (window.MutationObserver) new MutationObserver(function () {
      if (queued) return; queued = true;
      requestAnimationFrame(function () { queued = false; fit(); });
    }).observe(hb, { childList: true, subtree: true, characterData: true });
  }
  window.famaStar = function (sec, hb, photo, opts) {
    var name = (photo.match(/([\w-]+?)(-star)?\.(png|jpg)$/) || [])[1] || '';
    star(sec, hb, photo.replace(/-star\.png$/, '.jpg'), name, (opts && opts.text) || TEXT);
  };
  function boot() {
    document.querySelectorAll('section.fx-hero[data-fx-star]').forEach(function (sec) {
      if (sec.__fxStar) return; sec.__fxStar = 1;
      star(sec, sec.querySelector('.fx-hb'), sec.dataset.fxStar, sec.dataset.fxFocus || '', parseInt(sec.dataset.fxText || TEXT, 10));
    });
  }
  window.famaStarBoot = boot;
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
