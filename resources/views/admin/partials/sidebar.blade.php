{{-- The menu itself comes from App\Support\PanelNavigation, resolved once by
     the composer on the layout. Nothing here decides what is in the menu.

     `rail-*` classes are markers for the collapsed rail (see `admin-sidebar` in
     app.css): `rail-hide` is text that has no room at 4.5rem, `rail-center`
     centres what is left. Nav labels are not hidden — they stay in the
     accessible name and are shown as a tooltip on hover. --}}
<div class="rail-center flex items-center justify-between px-5 py-6">
    <a href="{{ route('admin.dashboard') }}" class="group flex items-center gap-3">
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-teal-500 font-display text-sm font-extrabold text-navy-950
                     transition duration-300 ease-out group-hover:scale-105 group-hover:shadow-[0_0_0_4px_rgb(23_166_152/0.2)]">
            RBR
        </span>
        <span class="rail-hide font-display text-sm font-bold text-white">{{ __('admin.panel') }}</span>
    </a>

    <button type="button" @click="menu = false"
            class="rail-hide rounded-lg p-1.5 text-white/60 transition duration-200 hover:rotate-90 hover:text-white lg:hidden">
        <span class="sr-only">{{ __('admin.actions.close') }}</span>
        <x-icon name="x" size="20" />
    </button>
</div>

<nav class="flex-1 overflow-y-auto px-3 pb-6">
    @foreach ($panelGroups as $group)
        @if ($group['heading'])
            {{-- In the rail this is a hairline rather than a caption: the
                 grouping still reads, the words have nowhere to go. --}}
            <p class="admin-nav-heading"><span class="rail-hide">{{ $group['label'] }}</span></p>
        @endif

        @foreach ($group['items'] as $item)
            <a href="{{ $item['url'] }}" data-panel-tip="{{ $item['label'] }}"
               class="{{ $item['active'] ? 'admin-nav-item-active' : 'admin-nav-item' }}"
               @if ($item['active']) aria-current="page" @endif>
                <x-icon :name="$item['icon']" size="18" />
                <span class="rail-label flex-1">{{ $item['label'] }}</span>

                @if (! empty($item['badge']))
                    {{-- Work waiting on the desk: the ring makes it findable in
                         peripheral vision without another colour in the sidebar.
                         Collapsed, it becomes a dot on the icon's shoulder. --}}
                    <span class="rail-badge rounded-full bg-teal-500 px-2 py-0.5 text-[11px] font-bold text-navy-950
                                 shadow-[0_0_0_3px_rgb(23_166_152/0.18)]">
                        {{ $item['badge'] }}
                    </span>
                @endif
            </a>
        @endforeach
    @endforeach
</nav>

<div class="rail-narrow border-t border-white/10 px-5 py-4">
    {{-- Whether the public site is on air, always in view. A panel that looks
         identical whether or not visitors can reach the site is how a
         maintenance switch gets left on over a weekend. --}}
    <a href="{{ route('admin.site.edit') }}" data-panel-tip="{{ feature('behaviour_maintenance') ? __('admin.site_status.maintenance') : __('admin.site_status.live') }}"
       class="rail-center mb-3 flex items-center gap-2 rounded-lg px-2 py-1.5 text-xs font-medium transition duration-200
              {{ feature('behaviour_maintenance') ? 'bg-urgent-600/15 text-urgent-100 hover:bg-urgent-600/25' : 'text-white/50 hover:bg-white/10 hover:text-white' }}">
        @if (feature('behaviour_maintenance'))
            <span class="pulse-dot text-urgent-500" aria-hidden="true"></span>
            <span class="rail-hide">{{ __('admin.site_status.maintenance') }}</span>
        @else
            <span class="pulse-dot text-teal-400" aria-hidden="true"></span>
            <span class="rail-hide">{{ __('admin.site_status.live') }}</span>
        @endif
    </a>

    <a href="{{ route('home') }}" target="_blank" rel="noopener" data-panel-tip="{{ __('admin.view_site') }}"
       class="rail-center group flex items-center gap-2 px-2 text-xs font-medium text-white/50 transition duration-200 hover:text-white">
        <x-icon name="external-link" size="14"
                class="transition-transform duration-300 ease-out group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
        <span class="rail-hide">{{ __('admin.view_site') }}</span>
    </a>

    {{-- The rail switch. At the foot rather than beside the logo because the
         collapsed sidebar is 4.5rem wide and the logo is already in it.
         Plain JS, not Alpine: the state lives on <html> and is settled by the
         inline script in the head, before the first paint. --}}
    <button type="button" data-panel-rail-toggle data-panel-tip="{{ __('admin.rail.expand') }}"
            aria-pressed="false"
            class="rail-center group mt-3 hidden w-full items-center gap-2 rounded-lg px-2 py-1.5 text-xs font-medium
                   text-white/50 transition duration-200 hover:bg-white/10 hover:text-white lg:flex">
        <x-icon name="panel-left" size="14" class="rail-flip transition-transform duration-300 ease-out" />
        <span class="rail-hide">{{ __('admin.rail.collapse') }}</span>
    </button>
</div>
