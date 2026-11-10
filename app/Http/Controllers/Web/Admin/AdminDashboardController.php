<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\Admin\PlatformAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly PlatformAnalyticsService $analytics,
    ) {}

    public function index(): View
    {
        Gate::authorize('access-admin-dashboard');

        return view('admin.dashboard', $this->analytics->overview());
    }

    public function storage(): View
    {
        Gate::authorize('access-admin-dashboard');

        return view('admin.storage.index', $this->analytics->storageBreakdown());
    }

    public function reports(): View
    {
        Gate::authorize('access-admin-dashboard');

        return view('admin.reports.index', $this->analytics->reports());
    }

    public function exportReport(string $type): StreamedResponse
    {
        Gate::authorize('access-admin-dashboard');

        if (! in_array($type, ['users', 'groups', 'mods', 'sync'], true)) {
            abort(404);
        }

        return $this->analytics->exportCsv($type);
    }

    public function auditLogs(Request $request): View
    {
        Gate::authorize('access-admin-dashboard');

        $search = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));
        $fromDate = trim((string) $request->query('from', ''));
        $toDate = trim((string) $request->query('to', ''));

        $logs = AuditLog::query()
            ->with(['user', 'device'])
            ->when($search !== '', function ($q) use ($search): void {
                $term = '%'.addcslashes($search, '\\%_').'%';
                $q->where(fn ($sub) => $sub
                    ->where('event', 'like', $term)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term)));
            })
            ->when($category !== '', function ($q) use ($category): void {
                $prefix = addcslashes($category, '\\%_').'.%';
                $q->where('event', 'like', $prefix);
            })
            ->when(preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate) === 1, function ($q) use ($fromDate): void {
                $q->whereDate('created_at', '>=', $fromDate);
            })
            ->when(preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate) === 1, function ($q) use ($toDate): void {
                $q->whereDate('created_at', '<=', $toDate);
            })
            ->select('id', 'group_id', 'user_id', 'device_id', 'event', 'auditable_type', 'auditable_id', 'old_values', 'new_values', 'created_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'search' => $search,
            'category' => $category,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
    }
}
