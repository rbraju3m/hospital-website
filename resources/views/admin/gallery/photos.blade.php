@php
    $locales = config('app.available_locales', []);
    $fallback = config('app.fallback_locale');
@endphp

{{-- The pictures themselves. Each is its own form — the album's fields above
     are already one, and HTML does not allow a form inside a form. Captions
     follow a single language switch for the whole section, the same way the
     album's fields follow the one at the top of the page. --}}
<div x-data="{ tab: '{{ $fallback }}' }">
    <x-admin.section :title="__('admin.gallery.photos')" :description="__('admin.gallery.photos_help')" :padded="false">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-mist-200 px-5 py-4">
            <p class="text-sm text-navy-900/60">
                {{ trans_choice('gallery.photos', $photos->count(), ['count' => number_format($photos->count())]) }}
            </p>
            <x-admin.locale-tabs />
        </div>

        @if ($photos->isEmpty())
            <p class="px-5 py-10 text-center text-sm text-navy-900/45">{{ __('admin.gallery.no_photos') }}</p>
        @else
            <div class="grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($photos as $photo)
                    <div class="overflow-hidden rounded-2xl border border-mist-200">
                        <span class="relative block aspect-[4/3] bg-mist-50">
                            @if ($photo->url())
                                <img src="{{ $photo->url() }}" alt="" class="h-full w-full object-cover">
                            @else
                                <span class="grid h-full w-full place-items-center text-navy-900/25">
                                    <x-icon name="image" size="22" />
                                </span>
                            @endif

                            @unless ($photo->untranslated('path'))
                                <span class="absolute inset-x-0 bottom-0 bg-navy-950/70 py-1 text-center text-[10px]
                                             font-semibold uppercase tracking-wide text-white">
                                    {{ __('admin.form.stand_in') }}
                                </span>
                            @endunless
                        </span>

                        <form method="POST" action="{{ route('admin.gallery.photos.update', [$album, $photo]) }}"
                              class="space-y-3 p-4">
                            @csrf
                            @method('PUT')

                            {{-- Written out per locale rather than through
                                 x-admin.translatable: that component reads old(),
                                 and every card on this page posts the same field
                                 names, so one rejected caption would repopulate
                                 all of them. --}}
                            @foreach ($locales as $code => $meta)
                                @php
                                    $isFallback = $code === $fallback;
                                    $value = $isFallback ? $photo->untranslated('caption') : $photo->translation('caption', $code);
                                @endphp

                                <label x-show="tab === '{{ $code }}'" @unless ($isFallback) x-cloak @endunless class="block">
                                    <span class="label-sm">
                                        {{ __('admin.fields.caption') }}
                                        @unless ($isFallback)
                                            <span class="ms-1 font-normal text-navy-900/40">{{ $meta['native'] }}</span>
                                        @endunless
                                    </span>
                                    <input type="text" lang="{{ $code }}" class="input input-sm"
                                           name="{{ $isFallback ? 'caption' : "translations[{$code}][caption]" }}"
                                           value="{{ $value }}">
                                </label>
                            @endforeach

                            <div class="flex items-end gap-2">
                                <label class="w-24">
                                    <span class="label-sm">{{ __('admin.fields.sort_order') }}</span>
                                    <input type="number" name="sort_order" value="{{ $photo->sort_order }}" class="input input-sm">
                                </label>

                                <button type="submit" class="btn-outline btn-sm">{{ __('admin.actions.save') }}</button>

                                <x-admin.delete-form :action="route('admin.gallery.photos.destroy', [$album, $photo])"
                                                     :confirm="__('admin.gallery.confirm_remove_photo')"
                                                     class="ms-auto" compact />
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </x-admin.section>

    <form method="POST" action="{{ route('admin.gallery.photos.store', $album) }}" enctype="multipart/form-data"
          class="admin-card mt-4 flex flex-wrap items-end gap-4 p-5">
        @csrf

        <div class="min-w-[16rem] flex-1">
            <label for="photos" class="label">{{ __('admin.gallery.add_photos') }}</label>

            <input id="photos" name="photos[]" type="file" accept="image/*" multiple
                   class="block w-full text-sm text-navy-900/70
                          file:me-3 file:rounded-lg file:border-0 file:bg-navy-900 file:px-4 file:py-2
                          file:text-sm file:font-semibold file:text-white file:transition file:duration-200
                          hover:file:bg-teal-600">

            <p class="mt-1.5 text-xs text-navy-900/45">
                {{ __('admin.gallery.add_photos_help', [
                    'count' => \App\Http\Requests\Admin\GalleryPhotoUploadRequest::MAX_PER_UPLOAD,
                    'size' => (int) (\App\Services\MediaLibrary::MAX_KILOBYTES / 1024),
                ]) }}
            </p>

            @error('photos') <p class="field-error">{{ $message }}</p> @enderror
            @error('photos.*') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn-primary btn-sm">
            <x-icon name="upload" size="15" />
            {{ __('admin.gallery.upload') }}
        </button>
    </form>
</div>
