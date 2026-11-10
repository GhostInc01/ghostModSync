<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\SyncApproval;
use App\Models\SyncSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AdminSyncController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('access-admin-dashboard');

        $status = trim((string) $request->query('status', ''));

        $sessions = SyncSession::query()
            ->with(['user', 'device', 'convoyProfile.group', 'items.modVersion.mod'])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        $pendingApprovals = SyncApproval::query()
            ->where('status', 'pending')
            ->with(['user', 'device', 'convoyProfile.group'])
            ->latest()
            ->limit(15)
            ->get();

        $recentDevices = Device::query()
            ->with('user')
            ->orderByDesc('last_seen_at')
            ->limit(15)
            ->get();

        return view('admin.sync.index', [
            'sessions' => $sessions,
            'pendingApprovals' => $pendingApprovals,
            'recentDevices' => $recentDevices,
            'status' => $status,
        ]);
    }
}
