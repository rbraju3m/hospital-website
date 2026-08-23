@extends('layouts.site')

@section('title', $package->name)
@section('meta_description', $package->summary)

@section('content')

<x-page-hero
    :eyebrow="str($package->category)->headline() . ' Package'"
    :title="$package->name"
    :lede="$package->summary"
    :crumbs="['Health Packages' => route('packages.index'), $package->name => null]" />

<section class="section">
    <div class="shell grid gap-12 lg:grid-cols-12">

        <div class="lg:col-span-8">
            <div class="space-y-4 text-base leading-relaxed text-navy-900/70">
                @foreach (preg_split('/\n+/', $package->description) as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            </div>

            @if ($package->tests)
                <h2 class="mt-14 font-display text-xl font-bold text-navy-900">
                    Tests included <span class="text-navy-900/40">({{ count($package->tests) }})</span>
                </h2>
                <ul class="mt-6 grid gap-3 sm:grid-cols-2">
                    @foreach ($package->tests as $test)
                        <li class="flex items-start gap-3 rounded-xl border border-mist-200 bg-white p-4">
                            <x-icon name="check" size="16" stroke="2.5" class="mt-0.5 shrink-0 text-teal-600" />
                            <span class="text-sm text-navy-900/75">{{ $test }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-14 rounded-[1.25rem] border border-mist-200 bg-mist-50 p-7">
                <h2 class="font-display text-lg font-bold text-navy-900">How the visit works</h2>
                <ol class="mt-6 space-y-5">
                    @foreach ([
                        ['Book a morning slot', 'Most tests need an 8–10 hour fast, so an early appointment is easiest. Water is fine.'],
                        ['Samples and imaging first', 'Blood, urine and imaging are taken in sequence in the health check lounge. Breakfast is provided afterwards.'],
                        ['Consultant review', 'A physician goes through every result with you and writes an action plan — this is included, not an add-on.'],
                        ['Report to keep', 'You leave with a printed report, and the same file is available in the patient portal for download later.'],
                    ] as $i => [$title, $body])
                        <li class="flex gap-4">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-navy-900 font-display text-sm font-bold text-white">
                                {{ $i + 1 }}
                            </span>
                            <div>
                                <p class="font-semibold text-navy-900">{{ $title }}</p>
                                <p class="mt-1 text-sm text-navy-900/60">{{ $body }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>

        <aside class="lg:col-span-4">
            <div class="sticky top-24 space-y-4">
                <div class="card p-7">
                    <div class="flex items-baseline gap-3">
                        <span class="font-display text-4xl font-extrabold text-navy-900">
                            ৳{{ number_format($package->effectivePrice()) }}
                        </span>
                        @if ($package->discount_price)
                            <span class="text-lg text-navy-900/40 line-through">৳{{ number_format($package->price) }}</span>
                        @endif
                    </div>

                    @if ($package->savingsPercent())
                        <p class="mt-2 inline-flex rounded-full bg-teal-50 px-3 py-1 text-xs font-bold text-teal-800">
                            Save {{ $package->savingsPercent() }}% — limited period
                        </p>
                    @endif

                    <dl class="mt-6 space-y-4 border-t border-mist-200 pt-6 text-sm">
                        <div class="flex gap-3">
                            <x-icon name="clock" size="18" class="mt-0.5 shrink-0 text-teal-600" />
                            <div>
                                <dt class="text-xs text-navy-900/50">Duration</dt>
                                <dd class="font-medium text-navy-900">{{ $package->duration }}</dd>
                            </div>
                        </div>
                        @if ($package->suitable_for)
                            <div class="flex gap-3">
                                <x-icon name="user-round" size="18" class="mt-0.5 shrink-0 text-teal-600" />
                                <div>
                                    <dt class="text-xs text-navy-900/50">Suitable for</dt>
                                    <dd class="font-medium text-navy-900">{{ $package->suitable_for }}</dd>
                                </div>
                            </div>
                        @endif
                        <div class="flex gap-3">
                            <x-icon name="file-text" size="18" class="mt-0.5 shrink-0 text-teal-600" />
                            <div>
                                <dt class="text-xs text-navy-900/50">Tests included</dt>
                                <dd class="font-medium text-navy-900">{{ count($package->tests ?? []) }} parameters</dd>
                            </div>
                        </div>
                    </dl>

                    <a href="{{ route('appointment.create') }}" class="btn-accent mt-7 w-full">Book this package</a>
                    <a href="tel:{{ setting('appointment_number') }}" class="btn-outline mt-2.5 w-full">
                        <x-icon name="phone" size="16" /> {{ setting('appointment_number') }}
                    </a>
                </div>
            </div>
        </aside>
    </div>
</section>

@if ($related->isNotEmpty())
    <section class="section bg-mist-50">
        <div class="shell">
            <x-section-heading eyebrow="Compare" title="Other packages" :link="route('packages.index')" link-label="All packages" class="reveal" />
            <div class="mt-12 grid gap-5 lg:grid-cols-3">
                @foreach ($related as $other)
                    <x-package-card :package="$other" class="reveal" />
                @endforeach
            </div>
        </div>
    </section>
@endif

@endsection
