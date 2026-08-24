@extends('portal.layouts.app')

@section('title', __('portal.payments.result_title'))

@section('content')
    <div class="mx-auto max-w-md">
        @if ($transaction->status === 'validated')
            <div class="card">
                <div class="px-5 py-8 text-center">
                    <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-teal-100">
                        <x-icon name="check-circle" class="text-teal-700" size="32" />
                    </span>
                    <h1 class="mt-4 text-xl font-bold text-navy-900">{{ __('portal.payments.success_title') }}</h1>
                    <p class="mt-2 text-sm text-navy-900/60">
                        {{ __('portal.payments.success_body', ['title' => $transaction->document->title]) }}
                    </p>
                    <p class="mt-4 text-lg font-bold text-navy-900">
                        ৳{{ number_format($transaction->amount) }}
                    </p>
                </div>
                <div class="border-t border-navy-900/5 px-5 py-4">
                    <a href="{{ route('portal.documents') }}" class="btn btn-primary w-full">
                        {{ __('portal.payments.back_to_bills') }}
                    </a>
                </div>
            </div>
        @elseif ($transaction->status === 'failed')
            <div class="card">
                <div class="px-5 py-8 text-center">
                    <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-red-100">
                        <x-icon name="x-circle" class="text-red-700" size="32" />
                    </span>
                    <h1 class="mt-4 text-xl font-bold text-navy-900">{{ __('portal.payments.fail_title') }}</h1>
                    <p class="mt-2 text-sm text-navy-900/60">
                        {{ __('portal.payments.fail_body') }}
                    </p>
                </div>
                <div class="border-t border-navy-900/5 px-5 py-4">
                    <a href="{{ route('portal.documents') }}" class="btn btn-primary w-full">
                        {{ __('portal.payments.back_to_bills') }}
                    </a>
                </div>
            </div>
        @elseif ($transaction->status === 'cancelled')
            <div class="card">
                <div class="px-5 py-8 text-center">
                    <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-amber-100">
                        <x-icon name="alert-circle" class="text-amber-700" size="32" />
                    </span>
                    <h1 class="mt-4 text-xl font-bold text-navy-900">{{ __('portal.payments.cancel_title') }}</h1>
                    <p class="mt-2 text-sm text-navy-900/60">
                        {{ __('portal.payments.cancel_body') }}
                    </p>
                </div>
                <div class="border-t border-navy-900/5 px-5 py-4">
                    <a href="{{ route('portal.documents') }}" class="btn btn-primary w-full">
                        {{ __('portal.payments.back_to_bills') }}
                    </a>
                </div>
            </div>
        @else
            <div class="card">
                <div class="px-5 py-8 text-center">
                    <div class="mx-auto h-16 w-16 animate-spin rounded-full border-4 border-navy-900/10 border-t-teal-600"></div>
                    <h1 class="mt-4 text-xl font-bold text-navy-900">{{ __('portal.payments.pending_title') }}</h1>
                    <p class="mt-2 text-sm text-navy-900/60">
                        {{ __('portal.payments.pending_body') }}
                    </p>
                </div>
                <div class="border-t border-navy-900/5 px-5 py-4">
                    <a href="{{ route('portal.documents') }}" class="btn btn-primary w-full">
                        {{ __('portal.payments.back_to_bills') }}
                    </a>
                </div>
            </div>
        @endif
    </div>
@endsection
