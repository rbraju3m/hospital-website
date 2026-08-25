{{-- The Ctrl+K palette. Its list is App\Support\PanelNavigation::palette(),
     resolved by the composer on the layout — the same registry the sidebar
     renders, so the two cannot disagree about what the panel contains.

     The whole thing is `x-cloak` because it starts closed: cloaking something
     that should be visible on load is what makes a page blink. --}}
<div x-data="panelPalette(@js($panelPalette))"
     @keydown.window="hotkey($event)"
     @panel-palette.window="show()">

    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-start justify-center px-4 pt-[12vh] sm:pt-[16vh]">
        <div x-show="open" @click="hide()" x-transition.opacity.duration.200ms
             class="absolute inset-0 bg-navy-950/50 dark:bg-navy-50/60 backdrop-blur-sm"></div>

        <div x-show="open" role="dialog" aria-modal="true" aria-label="{{ __('admin.palette.open') }}"
             x-transition:enter="transition duration-200 ease-out"
             x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition duration-150 ease-in"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative w-full max-w-xl overflow-hidden rounded-2xl border border-mist-200 bg-white dark:bg-navy-100 shadow-lift">

            <div class="flex items-center gap-3 border-b border-mist-200 px-4">
                <x-icon name="search" size="18" class="text-navy-900/35" />

                <input type="search" x-ref="input" x-model="query" @input="index = 0"
                       role="combobox" aria-expanded="true" aria-controls="panel-palette-list"
                       :aria-activedescendant="results.length ? `panel-palette-${index}` : null"
                       autocomplete="off" spellcheck="false"
                       placeholder="{{ __('admin.palette.placeholder') }}"
                       class="w-full border-0 bg-transparent py-4 text-sm text-navy-900 placeholder:text-navy-900/35 focus:outline-none">

                <button type="button" @click="hide()"
                        class="rounded-lg p-1.5 text-navy-900/40 transition duration-200 hover:bg-mist-100 hover:text-navy-900">
                    <span class="sr-only">{{ __('admin.actions.close') }}</span>
                    <x-icon name="x" size="16" />
                </button>
            </div>

            <ul id="panel-palette-list" role="listbox" x-ref="list" class="max-h-[52vh] overflow-y-auto p-2">
                <template x-for="(entry, i) in results" :key="entry.kind + entry.url">
                    <li>
                        {{-- A real link, so the palette can be middle-clicked
                             and opened in a tab like anything else. --}}
                        <a :href="entry.url" :id="`panel-palette-${i}`" role="option" tabindex="-1"
                           :aria-selected="i === index" @mousemove="index = i"
                           :class="i === index ? 'bg-teal-50/70 dark:bg-teal-500/10 text-navy-900' : 'text-navy-900/75'"
                           class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition duration-150">
                            <span :class="i === index ? 'text-teal-700' : 'text-navy-900/35'" class="grid place-items-center">
                                <span x-show="entry.kind === 'create'"><x-icon name="plus" size="16" /></span>
                                <span x-show="entry.kind !== 'create'"><x-icon name="arrow-right" size="16" /></span>
                            </span>

                            <span class="flex-1 truncate font-medium" x-text="entry.label"></span>

                            <span x-show="entry.badge" class="badge badge-teal" x-text="entry.badge"></span>

                            <span x-show="entry.gaps" class="badge badge-amber"
                                  :title="entry.gaps_label" x-text="entry.gaps"></span>

                            <span x-show="entry.group" class="hidden truncate text-xs text-navy-900/40 sm:block"
                                  x-text="entry.group"></span>
                        </a>
                    </li>
                </template>

                <li x-show="! results.length" class="px-3 py-6 text-center text-sm text-navy-900/50">
                    {{ __('admin.palette.empty') }}
                </li>
            </ul>

            <div class="hidden items-center gap-4 border-t border-mist-200 px-4 py-2.5 text-[11px] text-navy-900/45 sm:flex">
                <span class="flex items-center gap-1.5"><kbd class="kbd">↑</kbd><kbd class="kbd">↓</kbd> {{ __('admin.palette.hint_move') }}</span>
                <span class="flex items-center gap-1.5"><kbd class="kbd">↵</kbd> {{ __('admin.palette.hint_open') }}</span>
                <span class="flex items-center gap-1.5"><kbd class="kbd">esc</kbd> {{ __('admin.palette.hint_close') }}</span>
            </div>
        </div>
    </div>
</div>
