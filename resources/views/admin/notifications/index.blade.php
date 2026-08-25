@extends('admin.layouts.app')

@section('title', __('admin.nav.notifications'))
@section('heading', __('admin.nav.notifications'))
@section('subheading', __('admin.notifications.intro'))

@section('content')
    {{-- A queue worker that was never started loses every message in silence:
         the booking succeeds, nothing errors, and nothing arrives. This band is
         the one place that says so. --}}
    @if ($stuck)
        <div class="mb-6 flex flex-wrap items-center gap-4 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-amber-100 text-amber-700">
                <x-icon name="alert-triangle" size="19" />
            </span>

            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-amber-900">
                    {{ trans_choice('admin.notifications.stuck_warning', $stuck, ['count' => $stuck]) }}
                </p>
                <p class="mt-0.5 text-xs text-amber-900/70">{{ __('admin.notifications.stuck_help') }}</p>
            </div>

            <a href="{{ route('admin.notifications.index', ['status' => 'queued']) }}" class="btn-outline btn-sm shrink-0">
                {{ __('admin.notifications.queued') }}
            </a>
        </div>
    @endif

    <x-admin.list-header :placeholder="__('admin.notifications.search')">
        <select name="channel" class="input input-sm w-auto">
            <option value="">{{ __('admin.notifications.all_channels') }}</option>
            @foreach (['mail', 'sms'] as $channel)
                <option value="{{ $channel }}" @selected(request('channel') === $channel)>
                    {{ __("admin.notifications.{$channel}") }}
                </option>
            @endforeach
        </select>

        <select name="status" class="input input-sm w-auto">
            <option value="">{{ __('admin.notifications.all_statuses') }}</option>
            @foreach (['queued', 'sent', 'failed'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>
                    {{ __("admin.notifications.{$status}") }}
                </option>
            @endforeach
        </select>
    </x-admin.list-header>

    <div class="admin-card overflow-hidden">
        @if ($logs->isEmpty())
            <x-admin.empty :message="__('admin.notifications.empty')" icon="zap" />
        @else
            <ul>
                @foreach ($logs as $log)
                    <li class="admin-row flex items-start gap-4 px-5 py-4 first:border-0">
                        <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-mist-100 text-navy-900/50">
                            <x-icon :name="$log->channel === 'sms' ? 'message-circle' : 'mail'" size="17" />
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <span class="truncate text-sm font-semibold text-navy-900">{{ $log->recipient }}</span>

                                <span @class([
                                    'badge',
                                    'badge-teal' => $log->status === 'sent',
                                    'badge-amber' => $log->status === 'queued',
                                    'badge-urgent' => $log->status === 'failed',
                                ])>{{ __("admin.notifications.{$log->status}") }}</span>

                                <span class="badge badge-slate">{{ Str::upper($log->locale) }}</span>
                            </span>

                            <span class="mt-1 block truncate text-sm font-medium text-navy-900/75">
                                {{ __("admin.notifications.types.{$log->type}") }}
                            </span>

                            {{-- The SMS verbatim, because that is the record of
                                 what was actually said. An email's subject only:
                                 the body is a page of HTML nobody would read
                                 here, and it is written at send time anyway. --}}
                            @if ($log->body || $log->subject)
                                <span class="mt-0.5 block truncate text-xs text-navy-900/50">{{ $log->body ?: $log->subject }}</span>
                            @endif

                            @if ($log->error)
                                <span class="mt-1 block truncate text-xs text-urgent-700">{{ $log->error }}</span>
                            @endif

                            @if ($log->related instanceof \App\Models\Appointment)
                                <a href="{{ route('admin.appointments.show', $log->related) }}"
                                   class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-teal-700 hover:text-teal-800">
                                    {{ $log->related->reference }}
                                    <x-icon name="arrow-right" size="13" />
                                </a>
                            @endif
                        </span>

                        <span class="shrink-0 text-end text-xs text-navy-900/40">
                            <span class="block">{{ $log->created_at->translatedFormat('j M, H:i') }}</span>
                            @if ($log->sent_at)
                                <span class="block" title="{{ __('admin.notifications.not_delivery') }}">
                                    {{ __('admin.notifications.sent_at', ['time' => $log->sent_at->translatedFormat('H:i')]) }}
                                </span>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="mt-5">{{ $logs->links() }}</div>
@endsection
