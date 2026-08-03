/**
 * The fixed topbar's scroll behaviour (5AB): the seam hairline fades in once
 * the page has scrolled past 8px, and under 1100px the bar hides on
 * scroll-down / reveals on scroll-up (matching the feed chrome). One passive
 * listener, transform/opacity only.
 */
const topbar = document.querySelector('[data-topbar]');

if (topbar) {
    let lastY = window.scrollY;

    const onScroll = () => {
        const y = window.scrollY;

        topbar.classList.toggle('is-scrolled', y > 8);

        if (window.matchMedia('(max-width: 1099px)').matches && Math.abs(y - lastY) > 4) {
            topbar.classList.toggle('is-hidden', y > lastY && y > 120);
            lastY = y;
        } else if (y <= 120) {
            topbar.classList.remove('is-hidden');
            lastY = y;
        }
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
}
