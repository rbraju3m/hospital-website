<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ManagedLists;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The two things every listing in the panel can do without opening a record:
 * drag it into place, and switch it on or off.
 *
 * Both answer JSON and are driven from the table itself. Neither is the only
 * way to do the job — `sort_order` and the visibility toggle are still on the
 * edit form — so a browser without JavaScript loses a convenience, not a
 * capability.
 */
class ListController extends Controller
{
    public function reorder(Request $request, string $list): JsonResponse
    {
        abort_unless(ManagedLists::has($list) && ManagedLists::sortable($list), 404);

        $ids = collect($request->validate([
            'ids' => ['required', 'array', 'max:200'],
            'ids.*' => ['integer'],
        ])['ids'])->map(fn ($id) => (int) $id);

        $rows = ManagedLists::query($list)->whereKey($ids)->get(['id', 'sort_order']);

        // Renumber from the lowest position the dragged block already occupied,
        // rather than from 1. The listing is paginated: page two starting again
        // at 1 would shuffle it in among page one.
        $base = (int) $rows->min('sort_order');
        $known = $rows->keyBy('id');
        $position = 0;

        foreach ($ids as $id) {
            if (! $known->has($id)) {
                continue;
            }

            ManagedLists::query($list)->whereKey($id)->update(['sort_order' => $base + $position++]);
        }

        return response()->json(['ordered' => $position]);
    }

    public function toggle(Request $request, string $list, int $id): JsonResponse
    {
        abort_unless(ManagedLists::has($list), 404);

        $model = ManagedLists::query($list)->findOrFail($id);

        $model->forceFill(['is_active' => ! $model->is_active])->save();

        return response()->json([
            'active' => (bool) $model->is_active,
            'label' => $model->is_active ? __('admin.states.active') : __('admin.states.hidden'),
        ]);
    }
}
