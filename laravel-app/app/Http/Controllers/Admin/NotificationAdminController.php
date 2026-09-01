<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationAdminController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = Notification::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(40);

        return view('admin.notifications.index', compact('notifications'));
    }

    public function summary(Request $request): JsonResponse
    {
        $query = Notification::query()->where('user_id', $request->user()->id);

        return response()->json([
            'unread_count' => (clone $query)->where('is_read', false)->count(),
            'notifications' => $query
                ->latest()
                ->limit(5)
                ->get(['id', 'title', 'body', 'is_read', 'created_at'])
                ->map(fn (Notification $notification) => [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'body' => $notification->body,
                    'is_read' => $notification->is_read,
                    'created_at' => $notification->created_at?->diffForHumans(),
                ]),
        ]);
    }

    public function markRead(Request $request, Notification $notification): RedirectResponse
    {
        abort_if($notification->user_id !== $request->user()->id, 403);
        $notification->update(['is_read' => true]);

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        Notification::query()
            ->where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
