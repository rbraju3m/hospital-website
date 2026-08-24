@if (session('status'))
    <div class="alert-success mb-6">
        <x-icon name="check-circle" size="18" class="mt-0.5 shrink-0 text-teal-600" />
        <p>{{ session('status') }}</p>
    </div>
@endif

@if (session('warning'))
    <div class="alert-warning mb-6">
        <x-icon name="alert-triangle" size="18" class="mt-0.5 shrink-0 text-amber-600" />
        <p>{{ session('warning') }}</p>
    </div>
@endif

@if ($errors->any())
    <div class="alert-danger mb-6">
        <x-icon name="alert-triangle" size="18" class="mt-0.5 shrink-0 text-urgent-600" />
        <p class="font-semibold">{{ trans_choice('admin.validation_summary', $errors->count(), ['count' => $errors->count()]) }}</p>
    </div>
@endif
