@php
    $locales = config('app.available_locales', []);
    $fallback = config('app.fallback_locale');

    // The grid is driven by this array, not by markup: a photograph has to be
    // able to appear the moment its upload finishes, without a page reload.
    $rows = $photos->map(fn ($photo) => [
        'id' => $photo->id,
        'url' => $photo->url(),
        'uploaded' => filled($photo->untranslated('path')),
        'is_cover' => filled($photo->untranslated('path'))
            && $album->untranslated('image') === $photo->untranslated('path'),
        'captions' => collect($locales)->mapWithKeys(fn ($meta, $code) => [
            $code => $code === $fallback
                ? $photo->untranslated('caption')
                : $photo->translation('caption', $code),
        ])->all(),
    ])->values();
@endphp

{{-- A screen with no Save button. Files upload as they are dropped, a caption
     saves as it is typed, an order saves as it is dragged. --}}
<div x-data="albumMedia({
        photos: @js($rows),
        locales: @js(array_keys($locales)),
        fallback: @js($fallback),
        endpoints: {
            upload: @js(route('admin.gallery.photos.store', $album)),
            order: @js(route('admin.gallery.photos.order', $album)),
            {{-- __ID__ is swapped for the photograph's id in the browser:
                 one route generated once, rather than a URL per tile. --}}
            photo: @js(route('admin.gallery.photos.update', [$album, '__ID__'])),
            cover: @js(route('admin.gallery.photos.cover', [$album, '__ID__'])),
        },
        labels: {
            captionSaved: @js(__('admin.gallery.caption_saved')),
            coverSet: @js(__('admin.gallery.cover_set')),
            orderSaved: @js(__('admin.gallery.order_saved')),
            removed: @js(__('admin.gallery.photo_removed')),
            confirmDelete: @js(__('admin.gallery.confirm_remove_photo')),
            uploadFailed: @js(__('admin.gallery.upload_failed')),
            failed: @js(__('admin.lists.failed')),
        },
     })">

    <x-admin.section :title="__('admin.gallery.photos')" :description="__('admin.gallery.photos_help')" :padded="false">
        <div class="flex flex-wrap items-center gap-3 border-b border-mist-200 px-5 py-4">
            <p class="text-sm font-medium text-navy-900/60">
                <span x-text="photos.length"></span> {{ __('admin.gallery.photos') }}
            </p>

            {{-- What just happened, in place. There is no Save button to press
                 again, so a failure has to say so where it happened. --}}
            <p x-show="status" x-cloak x-transition
               :class="statusTone === 'error' ? 'bg-urgent-50 text-urgent-700' : 'bg-teal-50 text-teal-800'"
               class="flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold">
                <x-icon name="check" size="13" stroke="3" x-show="statusTone !== 'error'" />
                <x-icon name="alert-triangle" size="13" x-show="statusTone === 'error'" x-cloak />
                <span x-text="status"></span>
            </p>

            <div class="ms-auto"><x-admin.locale-tabs /></div>
        </div>

        <div class="p-5">
            {{-- Drop target. The whole panel accepts a drop, not just the
                 dashed box, because that is where the pointer ends up. --}}
            <div @dragover.prevent="hovering = true"
                 @dragleave="hovering = false"
                 @drop.prevent="onDrop($event)"
                 @click="choose()"
                 :class="hovering ? 'border-teal-500 bg-teal-50/60' : 'border-mist-200 hover:border-teal-300 hover:bg-mist-50'"
                 class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2
                        border-dashed px-6 py-8 text-center transition duration-200">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-teal-50 text-teal-700">
                    <x-icon name="upload" size="20" />
                </span>
                <p class="text-sm font-semibold text-navy-900">{{ __('admin.gallery.drop_here') }}</p>
                <p class="text-xs text-navy-900/45">
                    {{ __('admin.gallery.drop_help', ['size' => round(\App\Services\MediaLibrary::maxKilobytes() / 1024, 1)]) }}
                </p>

                <input x-ref="picker" type="file" accept="image/*" multiple class="hidden"
                       @change="fromPicker($event)" @click.stop>
            </div>

            {{-- In flight, one bar per picture. --}}
            <div x-show="uploads.length" x-cloak class="mt-4 space-y-2">
                <template x-for="ticket in uploads" :key="ticket.key">
                    <div class="rounded-xl border border-mist-200 px-4 py-3">
                        <div class="flex items-center justify-between gap-3 text-xs">
                            <span class="truncate font-medium text-navy-900/70" x-text="ticket.name"></span>
                            <span class="tabular-nums text-navy-900/45" x-text="ticket.progress + '%'"></span>
                        </div>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-mist-100">
                            <div class="h-full rounded-full bg-teal-500 transition-[width] duration-200"
                                 :style="'width: ' + ticket.progress + '%'"></div>
                        </div>
                    </div>
                </template>
            </div>

            <p x-show="! photos.length && ! uploads.length" x-cloak
               class="py-10 text-center text-sm text-navy-900/45">{{ __('admin.gallery.no_photos') }}</p>

            <div class="mt-5 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                <template x-for="(photo, i) in photos" :key="photo.id">
                    <div @dragover.prevent="over = i"
                         @dragleave="over === i && (over = null)"
                         @drop.prevent="dropOn(i)"
                         @dragend="dragging = null; over = null"
                         :class="{
                             'opacity-40': dragging === i,
                             'ring-2 ring-teal-500 ring-offset-2': over === i && dragging !== i,
                         }"
                         class="overflow-hidden rounded-2xl border border-mist-200 bg-white dark:bg-navy-100
                                transition duration-200">

                        <span class="relative block aspect-[4/3] bg-mist-50">
                            <img :src="photo.url" alt="" class="h-full w-full object-cover">

                            <span x-show="photo.is_cover" x-cloak
                                  class="absolute start-2 top-2 flex items-center gap-1 rounded-lg bg-teal-600 px-2 py-1
                                         text-[11px] font-bold text-white shadow-soft">
                                <x-icon name="star" size="12" />
                                {{ __('admin.gallery.cover') }}
                            </span>

                            <span x-show="! photo.uploaded" x-cloak
                                  class="absolute inset-x-0 bottom-0 bg-navy-950/70 py-1 text-center text-[10px]
                                         font-semibold uppercase tracking-wide text-white">
                                {{ __('admin.form.stand_in') }}
                            </span>
                        </span>

                        {{-- Controls sit under the picture rather than on top of
                             it: floating them over the photograph made both
                             harder to read. --}}
                        <div class="flex items-center gap-1 border-b border-mist-200 bg-mist-50/70 px-2 py-1.5">
                            <span draggable="true" @dragstart="dragStart(i, $event)"
                                  class="flex cursor-grab items-center px-1.5 py-1 text-navy-900/30
                                         hover:text-navy-900/60 active:cursor-grabbing"
                                  :title="@js(__('admin.lists.drag_help'))">
                                <x-icon name="grip" size="15" />
                            </span>

                            <button type="button" @click="move(i, -1)" :disabled="i === 0"
                                    class="rounded-lg p-1.5 text-navy-900/40 transition hover:bg-mist-200
                                           hover:text-navy-900 disabled:opacity-25"
                                    :title="@js(__('admin.gallery.move_up'))">
                                <x-icon name="chevron-up" size="15" />
                            </button>

                            <button type="button" @click="move(i, 1)" :disabled="i === photos.length - 1"
                                    class="rounded-lg p-1.5 text-navy-900/40 transition hover:bg-mist-200
                                           hover:text-navy-900 disabled:opacity-25"
                                    :title="@js(__('admin.gallery.move_down'))">
                                <x-icon name="chevron-down" size="15" />
                            </button>

                            <span class="ms-1 text-[11px] font-semibold text-navy-900/35" x-text="'#' + (i + 1)"></span>

                            <button type="button" @click="setCover(photo)" x-show="photo.uploaded && ! photo.is_cover"
                                    class="ms-auto rounded-lg p-1.5 text-navy-900/40 transition hover:bg-mist-200
                                           hover:text-amber-600"
                                    :title="@js(__('admin.gallery.use_as_cover'))">
                                <x-icon name="star" size="15" />
                            </button>

                            <button type="button" @click="remove(photo)"
                                    :class="photo.uploaded && ! photo.is_cover ? '' : 'ms-auto'"
                                    class="rounded-lg p-1.5 text-navy-900/40 transition hover:bg-urgent-50 hover:text-urgent-700"
                                    :title="@js(__('admin.actions.delete'))">
                                <x-icon name="trash" size="15" />
                            </button>
                        </div>

                        <div class="p-3">
                            @foreach ($locales as $code => $meta)
                                <label x-show="tab === '{{ $code }}'" @unless ($code === $fallback) x-cloak @endunless
                                       class="block">
                                    <span class="label-sm">
                                        {{ __('admin.fields.caption') }}
                                        @unless ($code === $fallback)
                                            <span class="ms-1 font-normal text-navy-900/40">{{ $meta['native'] }}</span>
                                        @endunless
                                    </span>
                                    <input type="text" lang="{{ $code }}" class="input input-sm"
                                           :value="photo.captions['{{ $code }}'] ?? ''"
                                           @input="caption(photo, '{{ $code }}', $event.target.value)"
                                           placeholder="{{ __('admin.gallery.caption_placeholder') }}">
                                </label>
                            @endforeach
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </x-admin.section>

    {{-- Without JavaScript there is no drop zone and no progress; the plain
         form still adds pictures. --}}
    <noscript>
        <form method="POST" action="{{ route('admin.gallery.photos.store', $album) }}" enctype="multipart/form-data"
              class="admin-card mt-4 flex flex-wrap items-end gap-4 p-5">
            @csrf
            <div class="min-w-[16rem] flex-1">
                <label for="photos" class="label">{{ __('admin.gallery.add_photos') }}</label>
                <input id="photos" name="photos[]" type="file" accept="image/*" multiple class="block w-full text-sm">
            </div>
            <button type="submit" class="btn-primary btn-sm">{{ __('admin.gallery.upload') }}</button>
        </form>
    </noscript>

    @error('photos') <p class="field-error mt-3">{{ $message }}</p> @enderror
</div>
