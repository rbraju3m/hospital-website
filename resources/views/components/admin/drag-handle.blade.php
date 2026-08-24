{{-- The row is only draggable while this is held: a permanently draggable row
     swallows text selection and turns every link in it into a drag. --}}
<span @mousedown="arm($event)" @mouseup="disarm($event)"
      class="flex cursor-grab items-center justify-center text-navy-900/25 transition
             hover:text-navy-900/60 active:cursor-grabbing"
      title="{{ __('admin.lists.drag_help') }}">
    <span class="sr-only">{{ __('admin.lists.drag_help') }}</span>
    <x-icon name="grip" size="16" />
</span>
