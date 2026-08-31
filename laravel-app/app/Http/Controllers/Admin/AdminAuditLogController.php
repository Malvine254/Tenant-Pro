<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use Illuminate\Http\Request;

class AdminAuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AdminAuditLog::query()
            ->with('actor:id,name,email')
            ->when($request->filled('action'), fn ($query) => $query->where('action', (string) $request->input('action')))
            ->when($request->filled('outcome'), function ($query) use ($request) {
                (string) $request->input('outcome') === 'failed'
                    ? $query->where('status_code', '>=', 400)
                    : $query->where('status_code', '<', 400);
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.trim((string) $request->input('search')).'%';
                $query->where(function ($match) use ($search) {
                    $match->where('action', 'like', $search)
                        ->orWhere('target_id', 'like', $search)
                        ->orWhere('request_id', 'like', $search)
                        ->orWhereHas('actor', fn ($actor) => $actor
                            ->where('name', 'like', $search)
                            ->orWhere('email', 'like', $search));
                });
            })
            ->latest('created_at')
            ->paginate(40)
            ->withQueryString();

        $actions = AdminAuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('admin.audit-logs.index', compact('logs', 'actions'));
    }
}
