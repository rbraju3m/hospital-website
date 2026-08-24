@props([
    'name',
    'label',
    'model' => null,
    'type' => 'text',   // text | textarea | list
    'rows' => 4,
    'help' => null,
    'required' => false,
    'placeholder' => null,
])

@php
    $locales = config('app.available_locales', []);
    $fallback = config('app.fallback_locale');
@endphp

<div {{ $attributes->merge(['class' => 'min-w-0']) }}>
    @foreach ($locales as $code => $meta)
        @php
            // The fallback locale lives in the ordinary column; every other
            // locale posts into translations[<locale>][<column>].
            $isFallback = $code === $fallback;
            $field = $isFallback ? $name : "translations[{$code}][{$name}]";
            $oldKey = $isFallback ? $name : "translations.{$code}.{$name}";
            $stored = $isFallback ? $model?->untranslated($name) : $model?->translation($name, $code);
            $current = old($oldKey, $type === 'list' ? array_to_lines($stored) : $stored);
            $id = 'f-'.$name.'-'.$code;
        @endphp

        {{-- Only the panes that start closed are cloaked: cloaking the open one
             too would blank every field on the form until Alpine boots. --}}
        <div x-show="tab === '{{ $code }}'" @unless ($isFallback) x-cloak @endunless>
            <label for="{{ $id }}" class="label">
                {{ $label }}
                @if ($required && $isFallback)
                    <span class="text-urgent-600" aria-hidden="true">*</span>
                @endif
                @unless ($isFallback)
                    <span class="ms-1 text-xs font-normal text-navy-900/40">{{ $meta['native'] }}</span>
                @endunless
            </label>

            @if ($type === 'text')
                <input id="{{ $id }}" name="{{ $field }}" type="text" value="{{ $current }}"
                       @if ($placeholder) placeholder="{{ $placeholder }}" @endif
                       class="input input-sm @error($oldKey) input-error @enderror" lang="{{ $code }}">

            @elseif ($type === 'richtext')
                {{-- A toolbar over a plain textarea. The public site renders a
                     small markup language and escapes everything else, so the
                     buttons write that markup rather than HTML nobody would be
                     able to trust. The preview renders it the same way PHP
                     will. Buttons use @mousedown.prevent so the caret never
                     leaves the text while one is pressed. --}}
                <div x-data="richText" class="editor"
                     data-link-prompt="{{ __('admin.editor.link_prompt') }}"
                     data-link-label="{{ __('admin.editor.link_label') }}">
                    <div class="editor-bar">
                        <button type="button" @mousedown.prevent @click="wrap('**')"
                                class="editor-btn" title="{{ __('admin.editor.bold') }} (Ctrl+B)">
                            <x-icon name="bold" size="15" />
                        </button>
                        <button type="button" @mousedown.prevent @click="wrap('_')"
                                class="editor-btn" title="{{ __('admin.editor.italic') }} (Ctrl+I)">
                            <x-icon name="italic" size="15" />
                        </button>
                        <button type="button" @mousedown.prevent @click="link()"
                                class="editor-btn" title="{{ __('admin.editor.link') }} (Ctrl+K)">
                            <x-icon name="link" size="15" />
                        </button>

                        <span class="editor-sep" aria-hidden="true"></span>

                        <button type="button" @mousedown.prevent @click="prefix('## ')"
                                class="editor-btn" title="{{ __('admin.editor.heading') }}">
                            <x-icon name="heading" size="15" />
                        </button>
                        <button type="button" @mousedown.prevent @click="prefix('### ')"
                                class="editor-btn text-[11px] font-bold" title="{{ __('admin.editor.subheading') }}">H3</button>

                        <span class="editor-sep" aria-hidden="true"></span>

                        <button type="button" @mousedown.prevent @click="prefix('- ')"
                                class="editor-btn" title="{{ __('admin.editor.bullet') }}">
                            <x-icon name="list" size="15" />
                        </button>
                        <button type="button" @mousedown.prevent @click="prefix('1. ', true)"
                                class="editor-btn" title="{{ __('admin.editor.numbered') }}">
                            <x-icon name="list-ordered" size="15" />
                        </button>
                        <button type="button" @mousedown.prevent @click="prefix('> ')"
                                class="editor-btn" title="{{ __('admin.editor.quote') }}">
                            <x-icon name="quote" size="15" />
                        </button>
                        <button type="button" @mousedown.prevent @click="replace(field().selectionStart, field().selectionEnd, '\n\n---\n\n')"
                                class="editor-btn" title="{{ __('admin.editor.divider') }}">
                            <x-icon name="minus" size="15" />
                        </button>

                        <button type="button" @mousedown.prevent @click="togglePreview()"
                                :class="preview && 'bg-navy-900 text-white hover:bg-navy-800'"
                                class="editor-btn ms-auto gap-1.5 px-2.5">
                            <x-icon name="eye" size="15" />
                            <span x-text="preview ? @js(__('admin.editor.write')) : @js(__('admin.editor.preview'))"></span>
                        </button>
                    </div>

                    <textarea id="{{ $id }}" name="{{ $field }}" rows="{{ $rows }}" x-ref="input"
                              x-show="! preview" @keydown="shortcut($event)"
                              @if ($placeholder) placeholder="{{ $placeholder }}" @endif
                              class="input input-sm rounded-t-none leading-relaxed @error($oldKey) input-error @enderror"
                              lang="{{ $code }}">{{ $current }}</textarea>

                    <div x-show="preview" x-cloak x-html="html"
                         class="min-h-[8rem] space-y-4 rounded-b-xl border border-t-0 border-mist-200 p-4
                                text-sm leading-relaxed text-navy-900/75"></div>
                </div>

            @else
                <textarea id="{{ $id }}" name="{{ $field }}" rows="{{ $type === 'list' ? min($rows, 6) : $rows }}"
                          @if ($placeholder) placeholder="{{ $placeholder }}" @endif
                          class="input input-sm leading-relaxed @error($oldKey) input-error @enderror"
                          lang="{{ $code }}">{{ $current }}</textarea>
            @endif

            @if ($help)
                <p class="mt-1.5 text-xs text-navy-900/45">{{ $help }}</p>
            @endif

            @if ($type === 'list')
                <p class="mt-1.5 text-xs text-navy-900/45">{{ __('admin.form.one_per_line') }}</p>
            @endif

            @error($oldKey)
                <p class="field-error">{{ $message }}</p>
            @enderror

            {{-- A translation left blank falls back to the source language, so
                 say so rather than letting it read as a missing page. --}}
            @if (! $isFallback && blank($current))
                <p class="mt-1.5 flex items-center gap-1.5 text-xs text-amber-700">
                    <x-icon name="languages" size="13" />
                    {{ __('admin.form.falls_back') }}
                </p>
            @endif
        </div>
    @endforeach
</div>
