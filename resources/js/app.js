import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

Alpine.plugin(collapse);
window.Alpine = Alpine;
Alpine.start();

/* ---------------------------------------------------------------------------
   Interaction layer.

   Everything below is progressive enhancement: each behaviour is additive, and
   the page reads and works with none of it. Motion is skipped wholesale when
   the visitor has asked for less of it — `reducedMotion` is read live rather
   than captured, because the OS setting can change while the tab is open.
   --------------------------------------------------------------------------- */

const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
const reducedMotion = () => motionQuery.matches;

/* rAF-coalesced scroll subscribers: one listener, one frame, however many
   readers. Each of these handlers reads layout, so batching them matters. */
const scrollHandlers = new Set();
let scrollQueued = false;

const runScrollHandlers = () => {
    scrollQueued = false;
    scrollHandlers.forEach((handler) => handler());
};

const onScroll = (handler) => {
    scrollHandlers.add(handler);
    handler();
};

window.addEventListener(
    'scroll',
    () => {
        if (scrollQueued) return;
        scrollQueued = true;
        requestAnimationFrame(runScrollHandlers);
    },
    { passive: true },
);

window.addEventListener('resize', () => requestAnimationFrame(runScrollHandlers), { passive: true });

/* --- Scroll reveal ---------------------------------------------------------
   Adds .reveal-in once an element scrolls into view. Elements opt in with
   class="reveal"; a container marked [data-reveal-stagger] hands its children
   an increasing --reveal-delay so a grid arrives as a wave rather than a wall.

   The children must carry `reveal` from the server. Adding the class here is a
   fallback for markup that forgot: an element that paints visible and is then
   hidden by JS flashes, which is worse than not animating it at all. */
const revealObserver = new IntersectionObserver(
    (entries) => {
        entries.forEach((entry) => {
            if (! entry.isIntersecting) return;
            entry.target.classList.add('reveal-in');
            revealObserver.unobserve(entry.target);
        });
    },
    { rootMargin: '0px 0px -8% 0px', threshold: 0.08 },
);

const observeReveals = (root = document) => {
    root.querySelectorAll('[data-reveal-stagger]').forEach((group) => {
        if (group.dataset.staggered === 'done') return;
        group.dataset.staggered = 'done';

        const step = Number(group.dataset.revealStagger) || 70;
        const cap = Number(group.dataset.revealStaggerMax) || 8;

        Array.from(group.children).forEach((child, index) => {
            child.classList.add('reveal');
            // Capped so a 40-row list does not end with a two-second wait.
            child.style.setProperty('--reveal-delay', `${Math.min(index, cap) * step}ms`);
        });
    });

    root.querySelectorAll('.reveal:not(.reveal-in)').forEach((el) => {
        if (reducedMotion()) {
            el.classList.add('reveal-in');
            return;
        }
        revealObserver.observe(el);
    });
};

/* --- Reading progress ------------------------------------------------------ */
const initScrollProgress = () => {
    const bar = document.querySelector('[data-scroll-progress]');
    if (! bar) return;

    onScroll(() => {
        const scrollable = document.documentElement.scrollHeight - window.innerHeight;
        const ratio = scrollable > 0 ? Math.min(window.scrollY / scrollable, 1) : 0;
        bar.style.setProperty('--progress', ratio.toFixed(4));
    });
};

/* --- Sticky header state --------------------------------------------------- */
const initHeader = () => {
    const header = document.querySelector('[data-site-header]');
    if (! header) return;

    onScroll(() => header.classList.toggle('is-scrolled', window.scrollY > 24));
};

/* --- Back to top ----------------------------------------------------------- */
const initBackToTop = () => {
    const button = document.querySelector('[data-to-top]');
    if (! button) return;

    onScroll(() => button.classList.toggle('is-visible', window.scrollY > window.innerHeight * 0.75));

    button.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: reducedMotion() ? 'auto' : 'smooth' });
    });
};

/* --- Counting statistics ---------------------------------------------------
   The final value is whatever the server rendered — the animation only counts
   up to it and then restores the original string verbatim, so a formatted or
   suffixed figure ("400+", "1,20,000") can never be mangled by the tween. */
const countUp = (el) => {
    const original = el.textContent.trim();
    const parts = original.match(/^(\D*)([\d.,]+)(\D*)$/);
    if (! parts) return;

    const target = Number(parts[2].replace(/,/g, ''));
    if (! Number.isFinite(target) || target <= 0) return;

    const grouped = parts[2].includes(',');
    const locale = document.documentElement.lang || 'en';
    const duration = Math.min(1600, 600 + target.toString().length * 180);
    const start = performance.now();

    el.style.fontVariantNumeric = 'tabular-nums';

    const frame = (now) => {
        const progress = Math.min((now - start) / duration, 1);
        // Same expo curve as the CSS easing, so counters settle with the cards.
        const eased = 1 - Math.pow(1 - progress, 4);
        const value = Math.round(target * eased);

        el.textContent = parts[1] + (grouped ? value.toLocaleString(locale) : value) + parts[3];

        if (progress < 1) {
            requestAnimationFrame(frame);
            return;
        }

        el.textContent = original;
    };

    requestAnimationFrame(frame);
};

const counterObserver = new IntersectionObserver(
    (entries) => {
        entries.forEach((entry) => {
            if (! entry.isIntersecting) return;
            counterObserver.unobserve(entry.target);
            countUp(entry.target);
        });
    },
    { threshold: 0.6 },
);

const observeCounters = (root = document) => {
    if (reducedMotion()) return;
    root.querySelectorAll('[data-countup]:not([data-counted])').forEach((el) => {
        el.dataset.counted = 'true';
        counterObserver.observe(el);
    });
};

/* --- Pointer spotlight on cards --------------------------------------------
   Writes --mx/--my on the hovered card; the gradient lives in CSS. Delegated
   from the document so cards rendered later are covered, and skipped on coarse
   pointers where there is no cursor to follow. */
const initCardSpotlight = () => {
    if (! window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;

    let pending = null;

    document.addEventListener(
        'pointermove',
        (event) => {
            const card = event.target.closest?.('.card-interactive, .admin-stat');
            if (! card) return;

            pending = { card, x: event.clientX, y: event.clientY };

            requestAnimationFrame(() => {
                if (! pending) return;
                const { card: target, x, y } = pending;
                pending = null;

                const rect = target.getBoundingClientRect();
                target.style.setProperty('--mx', `${((x - rect.left) / rect.width) * 100}%`);
                target.style.setProperty('--my', `${((y - rect.top) / rect.height) * 100}%`);
            });
        },
        { passive: true },
    );
};

/* --- Image fade-in ---------------------------------------------------------- */
const initImageFades = (root = document) => {
    root.querySelectorAll('img[data-fade]:not(.is-loaded)').forEach((img) => {
        if (img.complete && img.naturalWidth > 0) {
            img.classList.add('is-loaded');
            return;
        }
        img.addEventListener('load', () => img.classList.add('is-loaded'), { once: true });
        // A broken image should not stay invisible on top of being broken.
        img.addEventListener('error', () => img.classList.add('is-loaded'), { once: true });
    });
};

/* --- Boot ------------------------------------------------------------------- */
const enhance = (root = document) => {
    observeReveals(root);
    observeCounters(root);
    initImageFades(root);
};

document.addEventListener('DOMContentLoaded', () => {
    enhance();
    initScrollProgress();
    initHeader();
    initBackToTop();
    initCardSpotlight();

    /* Content that appears after boot — an Alpine collapse, a swapped tab —
       gets the same treatment without every component having to ask. Alpine can
       add nodes in bursts, so the rescan is coalesced into one frame. */
    let rescanQueued = false;

    const contentObserver = new MutationObserver((mutations) => {
        if (rescanQueued) return;

        const touched = mutations.some((mutation) =>
            Array.from(mutation.addedNodes).some((node) => node.nodeType === 1),
        );
        if (! touched) return;

        rescanQueued = true;
        requestAnimationFrame(() => {
            rescanQueued = false;
            enhance();
        });
    });

    contentObserver.observe(document.body, { childList: true, subtree: true });
});

/* If the visitor turns reduced motion on mid-visit, settle everything at once. */
motionQuery.addEventListener('change', () => {
    if (! reducedMotion()) return;
    document.querySelectorAll('.reveal:not(.reveal-in)').forEach((el) => el.classList.add('reveal-in'));
});
