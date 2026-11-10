<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\UpdateUserStatusRequest;
use App\Models\AuditLog;
use App\Models\Device;
use App\Models\SyncSession;
use App\Models\User;
use App\Services\Admin\PlatformModerationService;
use App\Services\Devices\DeviceRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function __construct(
        private readonly PlatformModerationService $moderation,
        private readonly DeviceRegistrationService $devices,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('access-admin-dashboard');

        $search = trim((string) $request->query('q', ''));
        $filter = trim((string) $request->query('filter', ''));

        $users = User::query()
            ->withCount(['groupMemberships', 'devices'])
            ->when($search !== '', function ($q) use ($search): void {
                $term = '%'.addcslashes($search, '\\%_').'%';
                $q->where(fn ($sub) => $sub
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('username', 'like', $term)
                    ->orWhere('steam_id', 'like', $term));
            })
            ->when($filter === 'suspended', fn ($q) => $q->whereNotNull('suspended_at'))
            ->when($filter === 'admins', fn ($q) => $q->where(fn ($s) => $s->where('is_platform_admin', true)->orWhereNotNull('platform_role')))
            ->when($filter === 'unverified', fn ($q) => $q->whereNull('email_verified_at'))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
            'filter' => $filter,
        ]);
    }

    public function show(User $user): View
    {
        Gate::authorize('access-admin-dashboard');

        $user->load(['groupMemberships.group', 'devices']);

        $auditLogs = AuditLog::query()
            ->where('user_id', $user->id)
            ->select('id', 'group_id', 'user_id', 'event', 'auditable_type', 'auditable_id', 'created_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        $syncSessions = SyncSession::query()
            ->where('user_id', $user->id)
            ->with(['convoyProfile.group', 'device'])
            ->latest('updated_at')
            ->limit(10)
            ->get();

        return view('admin.users.show', [
            'targetUser' => $user,
            'auditLogs' => $auditLogs,
            'syncSessions' => $syncSessions,
        ]);
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('access-admin-dashboard');

        /** @var User $actor */
        $actor = $request->user()->fresh();

        $this->moderation->updateUserStatus($actor, $user, $request->validated());

        return redirect()
            ->route('admin.users.show', $user)
            ->with('status', 'User account updated.');
    }

    public function revokeDevice(Request $request, User $user, Device $device): RedirectResponse
    {
        Gate::authorize('access-admin-dashboard');

        if ($device->user_id !== $user->id) {
            abort(404);
        }

        /** @var User $actor */
        $actor = $request->user()->fresh();

        if ($user->isSuperAdmin() && ! $actor->isSuperAdmin()) {
            abort(403);
        }

        $this->devices->revokeDevice($device, $actor);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('status', 'Device revoked.');
    }
}
