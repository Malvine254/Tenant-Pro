<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Notification::where('user_id', $request->user()->id)
            ->when($request->is_read !== null, fn($q) => $q->where('is_read', $request->boolean('is_read')));
        return response()->json(
            $query->latest()->get()->map(fn(Notification $notification) => $this->payload($notification))
        );
    }

    public function markRead(Request $request, Notification $notification)
    {
        abort_if($notification->user_id !== $request->user()->id, 403);
        $notification->update(['is_read' => true]);
        return response()->json($this->payload($notification->fresh()));
    }

    public function markAllRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)->update(['is_read' => true]);
        return response()->json(['message' => 'All notifications marked as read.']);
    }

    public function destroy(Request $request, Notification $notification)
    {
        abort_if($notification->user_id !== $request->user()->id, 403);
        $notification->delete();
        return response()->json(null, 204);
    }

    private function payload(Notification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'message' => $notification->body,
            'body' => $notification->body,
            'isRead' => (bool) $notification->is_read,
            'is_read' => (bool) $notification->is_read,
            'createdAt' => $notification->created_at?->toISOString(),
            'created_at' => $notification->created_at?->toISOString(),
            'readAt' => $notification->is_read ? $notification->updated_at?->toISOString() : null,
            'metadata' => $notification->metadata ?? [],
        ];
    }
}
