<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * What the system has said to whom.
 *
 * Read-only on purpose: the row is a record of something that already
 * happened, and an editable record of what was sent is not a record. There is
 * no resend button either — a second confirmation lands on a patient who has
 * one, and the desk can already re-trigger one by moving the status.
 */
class NotificationLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = NotificationLog::query()
            ->when($request->string('q')->trim()->value(), fn ($query, $term) => $query->where(
                fn ($inner) => $inner->where('recipient', 'like', "%{$term}%")
                    ->orWhere('subject', 'like', "%{$term}%")
                    ->orWhere('body', 'like', "%{$term}%")
            ))
            ->when(
                in_array($request->string('channel')->value(), ['mail', 'sms'], true),
                fn ($query) => $query->where('channel', $request->string('channel')->value()),
            )
            ->when(
                in_array($request->string('status')->value(), ['queued', 'sent', 'failed'], true),
                fn ($query) => $query->where('status', $request->string('status')->value()),
            )
            ->with('related')
            ->latestFirst()
            ->paginate(30)
            ->withQueryString();

        return view('admin.notifications.index', [
            'logs' => $logs,
            /* Queued half an hour ago and never confirmed. On a machine whose
               queue worker was never started this is every row ever written,
               which is exactly the point: the failure it stands for is
               otherwise completely silent. */
            'stuck' => NotificationLog::stuck()->count(),
            'failed' => NotificationLog::where('status', 'failed')->count(),
        ]);
    }
}
