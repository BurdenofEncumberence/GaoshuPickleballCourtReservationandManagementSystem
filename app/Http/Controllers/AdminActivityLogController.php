<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        ActivityLog::backfillIfEmpty();

        $role = (string) $request->query('role', 'all');
        $actionFilter = (string) $request->query('action', 'all');
        $dateFilter = (string) $request->query('date', 'all');
        $search = trim((string) $request->query('search', ''));

        $query = ActivityLog::with('user');

        if ($role !== 'all' && in_array($role, ['admin', 'manager', 'staff', 'player'], true)) {
            $query->where('user_role', $role);
        }

        if ($actionFilter !== 'all' && $actionFilter !== '') {
            $query->where('action', $actionFilter);
        }

        if ($dateFilter === 'today') {
            $query->whereDate('created_at', today());
        } elseif ($dateFilter === 'week') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($dateFilter === 'month') {
            $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                    ->orWhere('user_email', 'like', "%{$search}%")
                    ->orWhere('user_role', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $logs = $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $availableActions = ActivityLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $kpis = [
            'total' => ActivityLog::count(),
            'admin_manager' => ActivityLog::whereIn('user_role', ['admin', 'manager'])->count(),
            'staff' => ActivityLog::where('user_role', 'staff')->count(),
            'player' => ActivityLog::where('user_role', 'player')->count(),
            'today' => ActivityLog::whereDate('created_at', today())->count(),
        ];

        return view('admin.activity-logs.index', [
            'logs' => $logs,
            'availableActions' => $availableActions,
            'role' => $role,
            'actionFilter' => $actionFilter,
            'dateFilter' => $dateFilter,
            'search' => $search,
            'kpis' => $kpis,
        ]);
    }
}
