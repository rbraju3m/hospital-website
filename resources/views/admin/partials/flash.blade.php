@if (session('status'))
    <div class="mb-6 flex items-start gap-3 rounded-2xl border border-teal-200 bg-teal-50 px-5 py-4 text-sm text-teal-900">
        <x-icon name="check-circle" size="18" class="mt-0.5 text-teal-600" />
        <p>{{ session('status') }}</p>
    </div>
@endif

@if (session('warning'))
    <div class="mb-6 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
        <x-icon name="alert-triangle" size="18" class="mt-0.5 text-amber-600" />
        <p>{{ session('warning') }}</p>
    </div>
@endif

@if ($errors->any())
    <div class="mb-6 rounded-2xl border border-urgent-100 bg-urgent-50 px-5 py-4 text-sm text-urgent-700">
        <p class="font-semibold">{{ trans_choice('admin.validation_summary', $errors->count(), ['count' => $errors->count()]) }}</p>
    </div>
@endif
