import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

Alpine.plugin(collapse);
window.Alpine = Alpine;
Alpine.start();

/* Scroll-reveal: adds .reveal-in once an element scrolls into view.
   Elements opt in with class="reveal". Respects prefers-reduced-motion via CSS. */
const revealObserver = new IntersectionObserver(
    (entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('reveal-in');
                revealObserver.unobserve(entry.target);
            }
        });
    },
    { rootMargin: '0px 0px -10% 0px', threshold: 0.08 },
);

const observeReveals = () =>
    document.querySelectorAll('.reveal:not(.reveal-in)').forEach((el) => revealObserver.observe(el));

document.addEventListener('DOMContentLoaded', observeReveals);

/* Header shrinks / gains a solid background once the page is scrolled. */
document.addEventListener('DOMContentLoaded', () => {
    const header = document.querySelector('[data-site-header]');
    if (!header) return;

    const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 24);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
});
