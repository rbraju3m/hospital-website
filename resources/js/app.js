import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

/* Tell the page the bundle arrived. The head script hides revealable content on
   the assumption that this file will run; if it never does, that watchdog puts
   the content back rather than leaving a page of served-but-invisible images. */
document.documentElement.classList.add('js-ready');

Alpine.plugin(collapse);
window.Alpine = Alpine;

/* ---------------------------------------------------------------------------
   Gallery lightbox.

   Registered here rather than inline on the page for two reasons: it is the
   same component on every album, and a component defined in a <script> further
   down the document is one deploy away from being defined after Alpine has
   already walked the page.

   The viewer owns the keyboard while it is open (arrows, Home/End, F for
   fullscreen, Escape), locks the page behind it, and hands focus back to the
   tile it was opened from. Everything degrades: with no JavaScript the tiles
   are still links to the image itself.
   --------------------------------------------------------------------------- */
Alpine.data('galleryLightbox', (slides = []) => ({
    slides,
    index: null,
    trigger: null,
    zoomed: false,
    fullscreen: false,
    origin: '50% 50%',
    touch: null,

    init() {
        // The browser can leave fullscreen without us (Escape, the OS chrome),
        // so the flag follows the document rather than our own button.
        this.onFullscreenChange = () => {
            this.fullscreen = document.fullscreenElement !== null;
        };

        document.addEventListener('fullscreenchange', this.onFullscreenChange);
    },

    destroy() {
        document.removeEventListener('fullscreenchange', this.onFullscreenChange);
        this.unlock();
    },

    get slide() {
        return this.index === null ? null : this.slides[this.index] ?? null;
    },

    get isOpen() {
        return this.index !== null;
    },

    open(index, trigger = null) {
        if (! this.slides.length) return;

        this.index = Math.max(0, Math.min(index, this.slides.length - 1));
        this.trigger = trigger;
        this.resetZoom();

        document.body.classList.add('overflow-hidden');
        this.$nextTick(() => this.$refs.close?.focus());
        this.preload(this.index + 1);
    },

    close() {
        if (! this.isOpen) return;

        this.exitFullscreen();
        this.index = null;
        this.resetZoom();
        this.unlock();

        // Back to the tile it came from, or a keyboard user is dropped at the
        // top of the document with no idea where they were.
        this.trigger?.focus();
        this.trigger = null;
    },

    unlock() {
        document.body.classList.remove('overflow-hidden');
    },

    go(index) {
        if (! this.isOpen) return;

        const count = this.slides.length;

        this.index = ((index % count) + count) % count;
        this.resetZoom();
        this.preload(this.index + 1);
        this.scrollThumbIntoView();
    },

    next() {
        this.go(this.index + 1);
    },

    previous() {
        this.go(this.index - 1);
    },

    first() {
        this.go(0);
    },

    last() {
        this.go(this.slides.length - 1);
    },

    /* Fetch the neighbour while this one is being looked at, so moving through
       an album does not flash an empty frame on a slow connection. */
    preload(index) {
        const slide = this.slides[((index % this.slides.length) + this.slides.length) % this.slides.length];

        if (slide?.src) {
            new Image().src = slide.src;
        }
    },

    /* Click to magnify around the point that was clicked, click again to fit.
       Zoom is dropped whenever the picture changes — carrying it across slides
       leaves the next one showing a corner of itself. */
    toggleZoom(event) {
        if (this.zoomed) {
            this.resetZoom();

            return;
        }

        const box = event.currentTarget.getBoundingClientRect();
        const x = ((event.clientX - box.left) / box.width) * 100;
        const y = ((event.clientY - box.top) / box.height) * 100;

        this.origin = `${Math.min(100, Math.max(0, x))}% ${Math.min(100, Math.max(0, y))}%`;
        this.zoomed = true;
    },

    resetZoom() {
        this.zoomed = false;
        this.origin = '50% 50%';
    },

    toggleFullscreen() {
        if (document.fullscreenElement) {
            this.exitFullscreen();

            return;
        }

        // Safari on iOS has no element fullscreen; the viewer already covers
        // the viewport, so failing here costs nothing.
        this.$refs.dialog?.requestFullscreen?.().catch(() => {});
    },

    exitFullscreen() {
        if (document.fullscreenElement) {
            document.exitFullscreen?.().catch(() => {});
        }
    },

    onKey(event) {
        if (! this.isOpen) return;

        const keys = {
            ArrowRight: () => this.next(),
            ArrowLeft: () => this.previous(),
            Home: () => this.first(),
            End: () => this.last(),
            Escape: () => this.close(),
            f: () => this.toggleFullscreen(),
            F: () => this.toggleFullscreen(),
        };

        const handler = keys[event.key];

        if (! handler) return;

        // Escape in fullscreen belongs to the browser first: let it drop out of
        // fullscreen, and keep the viewer open.
        if (event.key === 'Escape' && document.fullscreenElement) return;

        event.preventDefault();
        handler();
    },

    touchStart(event) {
        this.touch = event.changedTouches[0]?.clientX ?? null;
    },

    touchEnd(event) {
        if (this.touch === null || this.zoomed) return;

        const delta = (event.changedTouches[0]?.clientX ?? this.touch) - this.touch;
        this.touch = null;

        if (Math.abs(delta) < 40) return;

        delta < 0 ? this.next() : this.previous();
    },

    scrollThumbIntoView() {
        this.$nextTick(() => {
            this.$refs.thumbs
                ?.querySelector('[data-current="true"]')
                ?.scrollIntoView({ block: 'nearest', inline: 'center' });
        });
    },
}));

/* ---------------------------------------------------------------------------
   The panel's album media manager.

   A screen with no Save button: files upload as they are dropped, a caption
   saves as it is typed, an order saves as it is dragged. Every action is one
   small JSON write, and the grid is driven by an array rather than by markup,
   so a photograph appears the moment its upload finishes instead of after a
   page reload.

   Files go up **one request each**. A batch big enough to pass `post_max_size`
   arrives with its body discarded — CSRF token included — and reads as an
   expired page; one at a time makes that impossible and buys a per-picture
   progress bar for free.
   --------------------------------------------------------------------------- */
Alpine.data('albumMedia', (config = {}) => ({
    photos: config.photos ?? [],
    endpoints: config.endpoints ?? {},
    labels: config.labels ?? {},
    locales: config.locales ?? [],
    tab: config.fallback,
    uploads: [],
    dragging: null,
    over: null,
    hovering: false,
    status: null,
    statusTone: 'saved',
    timers: {},

    csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    },

    say(message, tone = 'saved') {
        this.status = message;
        this.statusTone = tone;

        clearTimeout(this.timers.status);
        this.timers.status = setTimeout(() => (this.status = null), tone === 'error' ? 6000 : 1800);
    },

    /* --- adding pictures -------------------------------------------------- */

    choose() {
        this.$refs.picker?.click();
    },

    fromPicker(event) {
        this.add([...event.target.files]);
        event.target.value = '';
    },

    onDrop(event) {
        this.hovering = false;
        this.add([...(event.dataTransfer?.files ?? [])].filter((file) => file.type.startsWith('image/')));
    },

    async add(files) {
        for (const file of files) {
            await this.upload(file);
        }
    },

    upload(file) {
        const ticket = { key: `${file.name}-${this.uploads.length}-${file.size}`, name: file.name, progress: 0, error: null };
        this.uploads.push(ticket);

        return new Promise((resolve) => {
            const send = (payload) => {
                const form = new FormData();
                form.append('photos[]', payload, payload.name);

                const request = new XMLHttpRequest();
                request.open('POST', this.endpoints.upload);
                request.setRequestHeader('X-CSRF-TOKEN', this.csrf());
                request.setRequestHeader('Accept', 'application/json');

                request.upload.addEventListener('progress', (event) => {
                    if (event.lengthComputable) {
                        ticket.progress = Math.round((event.loaded / event.total) * 100);
                    }
                });

                request.addEventListener('load', () => {
                    this.uploads = this.uploads.filter((item) => item !== ticket);

                    if (request.status >= 200 && request.status < 300) {
                        const payload = JSON.parse(request.responseText || '{}');
                        this.photos = [...this.photos, ...(payload.photos ?? [])];
                    } else {
                        // 422 carries Laravel's own message; anything else is
                        // a server that did not want the file at all.
                        let message = this.labels.uploadFailed ?? 'Upload failed';

                        try {
                            const body = JSON.parse(request.responseText || '{}');
                            message = Object.values(body.errors ?? {}).flat()[0] ?? body.message ?? message;
                        } catch (error) {
                            // Keep the generic message.
                        }

                        this.say(`${file.name}: ${message}`, 'error');
                    }

                    resolve();
                });

                request.addEventListener('error', () => {
                    this.uploads = this.uploads.filter((item) => item !== ticket);
                    this.say(`${file.name}: ${this.labels.uploadFailed ?? 'Upload failed'}`, 'error');
                    resolve();
                });

                request.send(form);
            };

            // Shrink first, for the same reasons the rest of the panel does.
            shrinkImage(file).then(send).catch(() => send(file));
        });
    },

    /* --- editing ---------------------------------------------------------- */

    /* Captions save themselves a moment after typing stops. Saving on every
       keystroke would be a request per letter; saving on blur loses the last
       edit whenever somebody navigates away with the cursor still in a field. */
    caption(photo, locale, value) {
        photo.captions[locale] = value;

        clearTimeout(this.timers[`c${photo.id}`]);
        this.timers[`c${photo.id}`] = setTimeout(() => this.saveCaption(photo), 700);
    },

    async saveCaption(photo) {
        const body = { translations: {} };

        this.locales.forEach((locale) => {
            if (locale === this.tabFallback()) {
                body.caption = photo.captions[locale] ?? '';
            } else {
                body.translations[locale] = { caption: photo.captions[locale] ?? '' };
            }
        });

        await this.write('PATCH', this.endpoints.photo.replace('__ID__', photo.id), body, this.labels.captionSaved);
    },

    tabFallback() {
        return config.fallback ?? this.locales[0];
    },

    async setCover(photo) {
        if (! photo.uploaded) return;

        const done = await this.write('POST', this.endpoints.cover.replace('__ID__', photo.id), null, this.labels.coverSet);

        if (done) {
            this.photos = this.photos.map((item) => ({ ...item, is_cover: item.id === photo.id }));
        }
    },

    async remove(photo) {
        if (! window.confirm(this.labels.confirmDelete ?? 'Remove this photograph?')) return;

        const done = await this.write('DELETE', this.endpoints.photo.replace('__ID__', photo.id), null, this.labels.removed);

        if (done) {
            this.photos = this.photos.filter((item) => item.id !== photo.id);
        }
    },

    /* --- order ------------------------------------------------------------ */

    dragStart(index, event) {
        this.dragging = index;
        event.dataTransfer?.setData('text/plain', String(index));

        if (event.dataTransfer) event.dataTransfer.effectAllowed = 'move';
    },

    dropOn(index) {
        this.over = null;

        if (this.dragging === null || this.dragging === index) {
            this.dragging = null;

            return;
        }

        const photos = [...this.photos];
        const [moved] = photos.splice(this.dragging, 1);
        photos.splice(index, 0, moved);

        this.photos = photos;
        this.dragging = null;
        this.saveOrder();
    },

    move(index, delta) {
        const target = index + delta;

        if (target < 0 || target >= this.photos.length) return;

        const photos = [...this.photos];
        [photos[index], photos[target]] = [photos[target], photos[index]];
        this.photos = photos;
        this.saveOrder();
    },

    saveOrder() {
        return this.write(
            'POST',
            this.endpoints.order,
            { ids: this.photos.map((photo) => photo.id) },
            this.labels.orderSaved,
        );
    },

    /* --- the one place a request is made ---------------------------------- */

    async write(method, url, body, success) {
        try {
            const response = await fetch(url, {
                method,
                headers: {
                    'X-CSRF-TOKEN': this.csrf(),
                    Accept: 'application/json',
                    ...(body ? { 'Content-Type': 'application/json' } : {}),
                },
                ...(body ? { body: JSON.stringify(body) } : {}),
            });

            if (! response.ok) throw new Error(response.status);

            if (success) this.say(success);

            return true;
        } catch (error) {
            // The screen has already moved; say plainly that the database has
            // not, because there is no Save button to press again.
            this.say(this.labels.failed ?? 'That did not save', 'error');

            return false;
        }
    },
}));

Alpine.data('themeToggle', () => ({
    dark: document.documentElement.classList.contains('dark'),

    toggle() {
        this.dark = ! this.dark;
        document.documentElement.classList.toggle('dark', this.dark);

        try {
            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        } catch (error) {
            // Private browsing with storage blocked: the theme still applies
            // for this page, it just will not be remembered.
        }
    },
}));

/* Follow the device while no explicit choice has been made. */
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (event) => {
    try {
        if (localStorage.getItem('theme')) return;
    } catch (error) {
        return;
    }

    document.documentElement.classList.toggle('dark', event.matches);
});

/* ---------------------------------------------------------------------------
   Panel listings: drag a row into place, or switch a record on and off without
   opening it.

   Both write through fetch and both are additive — `sort_order` and the
   visibility toggle are still on the edit form, so a browser that cannot do
   this loses a convenience rather than a capability.
   --------------------------------------------------------------------------- */
Alpine.data('adminList', (config = {}) => ({
    list: config.list,
    sortable: config.sortable !== false,
    labels: config.labels ?? {},
    status: null,
    statusTone: 'saved',
    dragged: null,
    timer: null,

    csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    },

    say(message, tone = 'saved') {
        this.status = message;
        this.statusTone = tone;

        clearTimeout(this.timer);
        this.timer = setTimeout(() => (this.status = null), tone === 'error' ? 6000 : 2200);
    },

    /* --- reordering ------------------------------------------------------ */

    /* A row that is always draggable swallows text selection and turns every
       link in it into a drag. So the row is armed on mousedown over the handle
       and disarmed the moment the drag ends. */
    arm(event) {
        if (! this.sortable) return;

        const row = event.target.closest('[data-id]');

        if (row) row.draggable = true;
    },

    disarm(event) {
        const row = event.target.closest('[data-id]');

        if (row) row.draggable = false;
    },

    dragStart(event) {
        if (! this.sortable) return;

        this.dragged = event.target.closest('[data-id]');
        this.dragged?.classList.add('opacity-40');
        event.dataTransfer.effectAllowed = 'move';
        // Firefox refuses to start a drag with nothing on the transfer.
        event.dataTransfer.setData('text/plain', this.dragged?.dataset.id ?? '');
    },

    dragOver(event) {
        if (! this.dragged) return;

        const row = event.target.closest('[data-id]');

        if (! row || row === this.dragged || row.parentNode !== this.dragged.parentNode) return;

        // Insert before or after depending on which half of the row we are
        // over, so a drag reads as pushing the row aside rather than swapping.
        const box = row.getBoundingClientRect();
        const after = (event.clientY - box.top) / box.height > 0.5;

        row.parentNode.insertBefore(this.dragged, after ? row.nextSibling : row);
    },

    async dragEnd() {
        if (! this.dragged) return;

        const body = this.dragged.parentNode;
        this.dragged.classList.remove('opacity-40');
        this.dragged.draggable = false;
        this.dragged = null;

        const ids = [...body.querySelectorAll('[data-id]')].map((row) => Number(row.dataset.id));

        try {
            const response = await fetch(`/admin/lists/${this.list}/order`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrf(),
                    Accept: 'application/json',
                },
                body: JSON.stringify({ ids }),
            });

            if (! response.ok) throw new Error(response.status);

            this.say(this.labels.saved ?? 'Order saved');
        } catch (error) {
            // The rows have already moved on screen, so say plainly that the
            // page and the database now disagree.
            this.say(this.labels.failed ?? 'Could not save the order', 'error');
        }
    },

    /* --- the live switch -------------------------------------------------- */

    async toggle(id, event) {
        const input = event.target;
        const wanted = input.checked;

        try {
            const response = await fetch(`/admin/lists/${this.list}/${id}/toggle`, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': this.csrf(), Accept: 'application/json' },
            });

            if (! response.ok) throw new Error(response.status);

            const payload = await response.json();

            input.checked = payload.active;
            this.say(payload.label);
        } catch (error) {
            input.checked = ! wanted;
            this.say(this.labels.failed ?? 'Could not save that', 'error');
        }
    },
}));

/* ---------------------------------------------------------------------------
   The panel's writing surface.

   A toolbar over a plain textarea rather than a WYSIWYG, because the public
   site renders a deliberately small markup language — `## heading`, `- bullet`,
   `**bold**` — through x-article-body, which escapes everything first and then
   re-introduces only what it recognises. Storing HTML instead would mean
   trusting whatever an editor pasted, on pages a patient reads.

   So the buttons write that markup, and the preview renders it exactly the way
   PHP will, which is the part that makes it feel like an editor rather than a
   text box with instructions above it.
   --------------------------------------------------------------------------- */
const escapeHtml = (text) => text.replace(/[&<>"']/g, (character) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
}[character]));

/* Mirrors inline_markup() in app/Support/helpers.php. */
const inlineMarkup = (text) => escapeHtml(text)
    .replace(/\[([^\]]+)\]\(((?:[^()\s]|\([^()\s]*\))+)\)/g, (whole, label, url) => (
        /^(https?:\/\/|mailto:|tel:|\/|#)/i.test(url)
            ? `<a href="${url}" class="font-medium text-teal-700 underline underline-offset-2">${label}</a>`
            : label
    ))
    .replace(/\*\*(.+?)\*\*/g, '<strong class="font-semibold text-navy-900">$1</strong>')
    .replace(/(?<![\w*])_([^_]+)_(?![\w*])/g, '<em>$1</em>');

/* Mirrors resources/views/components/article-body.blade.php, block for block.
   If that changes, this changes with it — a preview that lies is worse than no
   preview. */
const renderMarkupLite = (body) => (body ?? '').trim().split(/\n\n+/).map((block) => {
    block = block.trim();

    if (! block) return '';

    const lines = block.split('\n');

    if (block.startsWith('## ')) {
        return `<h2 class="mt-8 font-display text-xl font-bold text-navy-900">${escapeHtml(block.slice(3))}</h2>`;
    }

    if (block.startsWith('### ')) {
        return `<h3 class="mt-6 font-display text-base font-bold text-navy-900">${escapeHtml(block.slice(4))}</h3>`;
    }

    if (block.startsWith('---')) {
        return '<hr class="my-6 border-mist-200">';
    }

    if (block.startsWith('> ')) {
        const quoted = lines.map((line) => line.replace(/^>\s*/, '')).join(' ');

        return `<blockquote class="border-s-4 border-teal-500 ps-4 italic">${inlineMarkup(quoted)}</blockquote>`;
    }

    if (block.startsWith('- ')) {
        const items = lines
            .map((line) => `<li class="flex gap-2"><span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-teal-500"></span><span>${inlineMarkup(line.replace(/^-\s*/, ''))}</span></li>`)
            .join('');

        return `<ul class="space-y-2">${items}</ul>`;
    }

    if (/^\d+\.\s/.test(block)) {
        const items = lines
            .map((line, index) => `<li class="flex gap-2"><span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-teal-50 text-[11px] font-bold text-teal-700">${index + 1}</span><span>${inlineMarkup(line.replace(/^\d+\.\s*/, ''))}</span></li>`)
            .join('');

        return `<ol class="space-y-2">${items}</ol>`;
    }

    return `<p>${inlineMarkup(block)}</p>`;
}).join('');

Alpine.data('richText', () => ({
    preview: false,
    html: '',

    field() {
        return this.$refs.input;
    },

    /* Write through the browser's own editing command where it exists, so
       Ctrl+Z still undoes what a button did. Assigning to `value` is faster to
       write and throws the undo stack away, which is exactly the kind of thing
       that makes an editor feel broken. */
    replace(start, end, text, select = null) {
        const field = this.field();

        field.focus();
        field.setSelectionRange(start, end);

        if (! document.execCommand?.('insertText', false, text)) {
            field.setRangeText(text, start, end, 'end');
        }

        if (select) {
            field.setSelectionRange(select[0], select[1]);
        }

        field.dispatchEvent(new Event('input', { bubbles: true }));
    },

    /* Wrap the selection, or drop the markers in and put the cursor between
       them — an empty selection should still leave you somewhere useful. */
    wrap(marker, closing = marker) {
        const field = this.field();
        const { selectionStart: start, selectionEnd: end, value } = field;
        const selected = value.slice(start, end);
        const before = value.slice(start - marker.length, start);
        const after = value.slice(end, end + closing.length);

        // Pressing bold on something already bold takes it off again.
        if (before === marker && after === closing) {
            this.replace(start - marker.length, end + closing.length, selected,
                [start - marker.length, end - marker.length]);

            return;
        }

        this.replace(start, end, marker + selected + closing,
            [start + marker.length, start + marker.length + selected.length]);
    },

    /* Line markers apply to every line the selection touches, so bulleting four
       lines is one click rather than four. */
    prefix(marker, numbered = false) {
        const field = this.field();
        const { selectionStart: start, selectionEnd: end, value } = field;

        const from = value.lastIndexOf('\n', start - 1) + 1;
        const to = value.indexOf('\n', end) === -1 ? value.length : value.indexOf('\n', end);
        const pattern = numbered ? /^\d+\.\s/ : null;

        const lines = value.slice(from, to).split('\n');
        const alreadyMarked = lines.every((line) => ! line.trim() || (numbered ? pattern.test(line) : line.startsWith(marker)));

        const block = lines.map((line, index) => {
            if (! line.trim()) return line;

            if (alreadyMarked) {
                return numbered ? line.replace(pattern, '') : line.slice(marker.length);
            }

            const stripped = numbered ? line.replace(pattern, '') : line.replace(new RegExp(`^${marker.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}`), '');

            return (numbered ? `${index + 1}. ` : marker) + stripped;
        }).join('\n');

        this.replace(from, to, block, [from, from + block.length]);
    },

    /* A link needs a destination, and a prompt is the whole dialog this needs.
       An empty answer leaves the text alone rather than writing []( ). */
    link() {
        const field = this.field();
        const { selectionStart: start, selectionEnd: end, value } = field;
        const selected = value.slice(start, end);
        const url = window.prompt(this.$el.dataset.linkPrompt ?? 'Link address', 'https://');

        if (! url) return;

        const label = selected || (this.$el.dataset.linkLabel ?? 'link');

        this.replace(start, end, `[${label}](${url})`);
    },

    shortcut(event) {
        if (! (event.metaKey || event.ctrlKey)) return;

        const actions = {
            b: () => this.wrap('**'),
            i: () => this.wrap('_'),
            k: () => this.link(),
        };

        const action = actions[event.key.toLowerCase()];

        if (! action) return;

        event.preventDefault();
        action();
    },

    togglePreview() {
        this.preview = ! this.preview;

        if (this.preview) {
            this.html = renderMarkupLite(this.field().value);
        }
    },
}));

Alpine.start();

/* ---------------------------------------------------------------------------
   Interaction layer.

   Everything below is progressive enhancement: each behaviour is additive, and
   the page reads and works with none of it. Motion is skipped wholesale when
   the visitor has asked for less of it — `reducedMotion` is read live rather
   than captured, because the OS setting can change while the tab is open.
   --------------------------------------------------------------------------- */

const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

/* Two ways to end up without motion: the visitor asked their device for less of
   it, or the hospital switched it off for everybody from Site controls (which
   renders `no-motion` on <html>). Read live rather than captured — the OS
   setting can change while the tab is open. */
const reducedMotion = () => motionQuery.matches || document.documentElement.classList.contains('no-motion');

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

/* The observer's failure mode is content that never appears, which is worse
   than content that never animates. This sweeps up anything still hidden that
   is plainly on screen — measured directly, rather than trusting the geometry
   the observer computed. */
const revealStragglers = () => {
    const hidden = document.querySelectorAll('.reveal:not(.reveal-in)');

    if (! hidden.length) return;

    hidden.forEach((el) => {
        const box = el.getBoundingClientRect();

        if (box.top < window.innerHeight && box.bottom > 0 && box.width > 0) {
            el.classList.add('reveal-in');
        }
    });
};

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

/* --- Parallax layers -------------------------------------------------------
   Decorative photography drifts against the page as it scrolls. The factor is
   read from the element (`data-parallax="0.1"`), kept low on purpose — enough
   to separate the layer from the text over it, not enough to notice as an
   effect — and the whole thing is skipped when motion is not wanted. */
const initParallax = () => {
    const layers = Array.from(document.querySelectorAll('[data-parallax]'));
    if (! layers.length) return;

    onScroll(() => {
        if (reducedMotion()) {
            layers.forEach((layer) => layer.style.removeProperty('translate'));
            return;
        }

        layers.forEach((layer) => {
            const rect = layer.getBoundingClientRect();
            // Skip anything off screen: this runs on every scroll frame.
            if (rect.bottom < 0 || rect.top > window.innerHeight) return;

            const factor = Number(layer.dataset.parallax) || 0.1;
            const offset = (rect.top + rect.height / 2 - window.innerHeight / 2) * factor;
            layer.style.translate = `0 ${offset.toFixed(1)}px`;
        });
    });
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

/* ---------------------------------------------------------------------------
   Shrink pictures in the browser before they are uploaded.

   A phone photograph is 4–8 MB and a screenshot is often a 1.5 MB PNG, while
   PHP here accepts 2 MB per file and 8 MB for a whole request — so six pictures
   chosen at once used to arrive as nothing at all, because a request over
   `post_max_size` is discarded body and all before Laravel sees it.

   Resizing to a sensible edge and re-encoding as JPEG turns those six into a
   couple of hundred kilobytes each, which is both a working upload and a page
   that loads. The original is kept whenever this cannot improve on it: an image
   that is already small, a format worth preserving, or a browser that refuses.
   --------------------------------------------------------------------------- */
const UPLOAD_MAX_EDGE = 2400;
const UPLOAD_QUALITY = 0.82;
// Animation and vector art do not survive a trip through a canvas.
const UPLOAD_KEEP_AS_IS = ['image/gif', 'image/svg+xml'];

const megabytes = (bytes) => `${(bytes / 1024 / 1024).toFixed(1)} MB`;

const shrinkImage = async (file) => {
    if (! file.type.startsWith('image/') || UPLOAD_KEEP_AS_IS.includes(file.type)) return file;

    let bitmap;

    try {
        bitmap = await createImageBitmap(file);
    } catch (error) {
        return file;
    }

    const scale = Math.min(1, UPLOAD_MAX_EDGE / Math.max(bitmap.width, bitmap.height));
    const width = Math.round(bitmap.width * scale);
    const height = Math.round(bitmap.height * scale);

    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;

    const context = canvas.getContext('2d');
    // JPEG has no alpha channel: without this, anything transparent comes out
    // black rather than white.
    context.fillStyle = '#ffffff';
    context.fillRect(0, 0, width, height);
    context.drawImage(bitmap, 0, 0, width, height);
    bitmap.close?.();

    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', UPLOAD_QUALITY));

    // Never hand back something bigger than what was chosen.
    if (! blob || blob.size >= file.size) return file;

    return new File([blob], `${file.name.replace(/\.[^.]+$/, '')}.jpg`, {
        type: 'image/jpeg',
        lastModified: file.lastModified,
    });
};

const uploadStatusNode = (input) => {
    let node = input.parentElement?.querySelector('[data-compress-status]');

    if (! node) {
        node = document.createElement('p');
        node.dataset.compressStatus = '';
        node.className = 'mt-1.5 text-xs font-medium text-teal-700';
        input.insertAdjacentElement('afterend', node);
    }

    return node;
};

/* A form submitted while pictures are still being prepared would send the
   originals — the very thing this exists to avoid. Hold the submit, and let it
   go the moment the last one is ready. */
let uploadsPending = 0;
const heldSubmits = new Set();

document.addEventListener('submit', (event) => {
    if (uploadsPending === 0) return;

    event.preventDefault();
    heldSubmits.add(event.target);
}, true);

const releaseHeldSubmits = () => {
    if (uploadsPending > 0) return;

    heldSubmits.forEach((form) => form.requestSubmit());
    heldSubmits.clear();
};

const initUploadShrinking = (root = document) => {
    root.querySelectorAll('input[type="file"][data-compress]:not([data-compress-ready])').forEach((input) => {
        input.dataset.compressReady = '1';

        input.addEventListener('change', async () => {
            const chosen = [...(input.files ?? [])];

            if (! chosen.length) return;

            const note = uploadStatusNode(input);
            note.textContent = input.dataset.compressBusy ?? '';

            uploadsPending++;
            input.dispatchEvent(new CustomEvent('compress:start', { bubbles: true }));

            const before = chosen.reduce((total, file) => total + file.size, 0);
            const shrunk = [];

            try {
                for (const file of chosen) {
                    shrunk.push(await shrinkImage(file));
                }
            } finally {
                uploadsPending--;
                input.dispatchEvent(new CustomEvent('compress:done', { bubbles: true }));
            }

            const after = shrunk.reduce((total, file) => total + file.size, 0);

            try {
                const transfer = new DataTransfer();
                shrunk.forEach((file) => transfer.items.add(file));
                input.files = transfer.files;
            } catch (error) {
                // Older browser: the originals are still on the input and the
                // server limits still apply, which is where we started.
                note.textContent = '';
                releaseHeldSubmits();

                return;
            }

            note.textContent = after < before
                ? (input.dataset.compressDone ?? '')
                    .replace(':count', String(shrunk.length))
                    .replace(':before', megabytes(before))
                    .replace(':after', megabytes(after))
                : '';

            releaseHeldSubmits();
        });
    });
};

/* ---------------------------------------------------------------------------
   The staff panel's collapsible icon rail.

   The state is a class on <html>, put there by the inline script in the head
   before the first paint; this only flips it and remembers the choice. Plain
   JS rather than Alpine for that reason — Alpine boots from a module script,
   i.e. after the first paint, and a menu that resizes itself once the page is
   already up is the thing the head script exists to avoid.

   Collapsed, a link's label is `sr-only` and named by a tooltip instead. That
   tooltip is one fixed node in the layout rather than something drawn inside
   the link: the nav scrolls, and an overflow container clips its own children.
   --------------------------------------------------------------------------- */
const RAIL_KEY = 'panel-rail';

const initPanelRail = () => {
    const toggle = document.querySelector('[data-panel-rail-toggle]');
    if (! toggle) return;

    const railed = () => document.documentElement.classList.contains(RAIL_KEY);

    toggle.setAttribute('aria-pressed', railed() ? 'true' : 'false');

    toggle.addEventListener('click', () => {
        const next = ! railed();
        document.documentElement.classList.toggle(RAIL_KEY, next);
        toggle.setAttribute('aria-pressed', next ? 'true' : 'false');
        hideTip();

        try {
            localStorage.setItem(RAIL_KEY, next ? '1' : '0');
        } catch (error) {
            // Storage blocked: the menu still collapses, it just forgets.
        }
    });
};

const tipBox = () => document.querySelector('[data-panel-tip-box]');

const hideTip = () => {
    const tip = tipBox();
    if (tip) tip.hidden = true;
};

const showTip = (target) => {
    const tip = tipBox();
    const label = target.getAttribute('data-panel-tip');
    if (! tip || ! label) return;

    tip.textContent = label;
    tip.hidden = false;

    const box = target.getBoundingClientRect();
    tip.style.top = `${box.top + box.height / 2}px`;
    tip.style.left = `${box.right + 10}px`;
};

const initPanelTips = () => {
    if (! tipBox()) return;

    const handle = (event) => {
        // Only while collapsed: expanded, the label is already on the screen.
        if (! document.documentElement.classList.contains(RAIL_KEY)) return hideTip();

        const target = event.target.closest?.('[data-panel-tip]');
        target ? showTip(target) : hideTip();
    };

    document.addEventListener('mouseover', handle);
    document.addEventListener('focusin', handle);

    // A tooltip pinned to the viewport goes stale the moment anything moves.
    document.addEventListener('mouseleave', hideTip);
    window.addEventListener('resize', hideTip);
    window.addEventListener('scroll', hideTip, true);
};

const enhance = (root = document) => {
    observeReveals(root);
    observeCounters(root);
    initImageFades(root);
    initUploadShrinking(root);
};

document.addEventListener('DOMContentLoaded', () => {
    enhance();
    onScroll(revealStragglers);
    setTimeout(revealStragglers, 1200);
    initScrollProgress();
    initHeader();
    initBackToTop();
    initCardSpotlight();
    initParallax();
    initPanelRail();
    initPanelTips();

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
