@php
    $days = collect(range(0, 6))->mapWithKeys(fn ($day) => [$day => \App\Models\DoctorSchedule::dayLabel($day)])->all();
@endphp

{{-- Chamber hours are a weekly pattern, not a calendar: the booking engine
     expands them per date, so a change here takes effect from the next
     matching day. Each row is its own form — nesting forms is not allowed. --}}
<x-admin.section :title="__('admin.doctors.schedule')" :description="__('admin.doctors.schedule_help')" :padded="false">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[54rem]">
            <thead class="bg-mist-50">
                <tr>
                    <th class="admin-th">{{ __('admin.fields.day_of_week') }}</th>
                    <th class="admin-th">{{ __('admin.fields.start_time') }}</th>
                    <th class="admin-th">{{ __('admin.fields.end_time') }}</th>
                    <th class="admin-th">{{ __('admin.fields.slot_minutes') }}</th>
                    <th class="admin-th">{{ __('admin.fields.capacity_per_slot') }}</th>
                    <th class="admin-th">{{ __('admin.fields.location') }}</th>
                    <th class="admin-th"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($doctor->schedules as $schedule)
                    <tr class="admin-row">
                        <td colspan="7" class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.doctors.schedules.update', [$doctor, $schedule]) }}"
                                  class="flex flex-wrap items-end gap-3">
                                @csrf
                                @method('PUT')

                                <label class="min-w-[8rem]">
                                    <span class="label-sm">{{ __('admin.fields.day_of_week') }}</span>
                                    <select name="day_of_week" class="input input-sm">
                                        @foreach ($days as $value => $label)
                                            <option value="{{ $value }}" @selected($schedule->day_of_week === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label>
                                    <span class="label-sm">{{ __('admin.fields.start_time') }}</span>
                                    <input type="time" name="start_time" value="{{ substr($schedule->start_time, 0, 5) }}" class="input input-sm">
                                </label>

                                <label>
                                    <span class="label-sm">{{ __('admin.fields.end_time') }}</span>
                                    <input type="time" name="end_time" value="{{ substr($schedule->end_time, 0, 5) }}" class="input input-sm">
                                </label>

                                <label class="w-24">
                                    <span class="label-sm">{{ __('admin.fields.slot_minutes') }}</span>
                                    <input type="number" name="slot_minutes" value="{{ $schedule->slot_minutes }}" class="input input-sm">
                                </label>

                                <label class="w-24">
                                    <span class="label-sm">{{ __('admin.fields.capacity_per_slot') }}</span>
                                    <input type="number" name="capacity_per_slot" value="{{ $schedule->capacity_per_slot }}" class="input input-sm">
                                </label>

                                <label class="min-w-[10rem] flex-1">
                                    <span class="label-sm">{{ __('admin.fields.location') }}</span>
                                    <input type="text" name="location" value="{{ $schedule->location }}" class="input input-sm">
                                </label>

                                <label class="flex items-center gap-2 pb-2.5 text-xs text-navy-900/70">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" @checked($schedule->is_active)
                                           class="h-4 w-4 rounded border-mist-200 text-teal-600 focus:ring-teal-500/30">
                                    {{ __('admin.states.active') }}
                                </label>

                                <button type="submit" class="btn-outline btn-sm">{{ __('admin.actions.save') }}</button>
                            </form>

                            <x-admin.delete-form :action="route('admin.doctors.schedules.destroy', [$doctor, $schedule])"
                                                 :confirm="__('admin.doctors.confirm_remove_schedule')"
                                                 class="mt-2 block" compact />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-navy-900/45">
                            {{ __('admin.doctors.no_schedules') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <form method="POST" action="{{ route('admin.doctors.schedules.store', $doctor) }}"
          class="flex flex-wrap items-end gap-3 border-t border-mist-200 bg-mist-50 px-4 py-4">
        @csrf

        <label class="min-w-[8rem]">
            <span class="label-sm">{{ __('admin.fields.day_of_week') }}</span>
            <select name="day_of_week" class="input input-sm">
                @foreach ($days as $value => $label)
                    <option value="{{ $value }}" @selected(old('day_of_week') == $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>

        <label>
            <span class="label-sm">{{ __('admin.fields.start_time') }}</span>
            <input type="time" name="start_time" value="{{ old('start_time', '17:00') }}" class="input input-sm">
        </label>

        <label>
            <span class="label-sm">{{ __('admin.fields.end_time') }}</span>
            <input type="time" name="end_time" value="{{ old('end_time', '20:00') }}" class="input input-sm">
        </label>

        <label class="w-24">
            <span class="label-sm">{{ __('admin.fields.slot_minutes') }}</span>
            <input type="number" name="slot_minutes" value="{{ old('slot_minutes', 20) }}" class="input input-sm">
        </label>

        <label class="w-24">
            <span class="label-sm">{{ __('admin.fields.capacity_per_slot') }}</span>
            <input type="number" name="capacity_per_slot" value="{{ old('capacity_per_slot', 1) }}" class="input input-sm">
        </label>

        <label class="min-w-[10rem] flex-1">
            <span class="label-sm">{{ __('admin.fields.location') }}</span>
            <input type="text" name="location" value="{{ old('location') }}" class="input input-sm"
                   placeholder="{{ __('admin.doctors.location_placeholder') }}">
        </label>

        <input type="hidden" name="is_active" value="1">

        <button type="submit" class="btn-accent btn-sm">
            <x-icon name="plus" size="15" stroke="2.5" />
            {{ __('admin.doctors.add_schedule') }}
        </button>
    </form>
</x-admin.section>
