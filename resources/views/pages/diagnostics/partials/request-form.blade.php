{{-- A call-back request, not a booking: nothing is scheduled and nothing is
     charged. It lands in the same inbox as the contact form. --}}
<div id="request" class="card p-7 lg:sticky lg:top-24">
    <h2 class="font-display text-lg font-bold text-navy-900">{{ __('diagnostics.request.title') }}</h2>
    <p class="mt-1.5 text-sm text-navy-900/60">{{ __('diagnostics.request.lede') }}</p>

    @if (session('status'))
        <div class="mt-5 flex items-start gap-3 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900">
            <x-icon name="check-circle" size="18" class="mt-0.5 shrink-0 text-teal-600" />
            <p>{{ session('status') }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('diagnostics.request', $test) }}" class="mt-5 space-y-4">
        @csrf

        <div>
            <label for="name" class="label">{{ __('diagnostics.request.name') }}</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required
                   class="input @error('name') input-error @enderror">
            @error('name') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="phone" class="label">{{ __('diagnostics.request.phone') }}</label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required
                   inputmode="numeric" placeholder="01XXXXXXXXX"
                   class="input @error('phone') input-error @enderror">
            @error('phone') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="label">
                {{ __('diagnostics.request.email') }}
                <span class="font-normal text-navy-900/40">{{ __('common.optional') }}</span>
            </label>
            <input id="email" name="email" type="email" value="{{ old('email') }}"
                   class="input @error('email') input-error @enderror">
            @error('email') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="notes" class="label">
                {{ __('diagnostics.request.notes') }}
                <span class="font-normal text-navy-900/40">{{ __('common.optional') }}</span>
            </label>
            <textarea id="notes" name="notes" rows="3"
                      placeholder="{{ __('diagnostics.request.notes_placeholder') }}"
                      class="input @error('notes') input-error @enderror">{{ old('notes') }}</textarea>
            @error('notes') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn-primary w-full">{{ __('diagnostics.request.submit') }}</button>
    </form>
</div>
