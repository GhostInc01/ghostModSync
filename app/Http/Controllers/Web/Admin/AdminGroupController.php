<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\UpdateGroupAdminRequest;
use App\Models\Group;
use App\Models\ModerationReport;
use App\Models\User;
use App\Services\Admin\PlatformModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AdminGroupController extends Controller
{
    public function __construct(
        private readonly PlatformModerationService $moderation,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('access-admin-dashboard');

        $search = trim((string) $request->query('q', ''));
        $filter = trim((string) $request->query('filter', ''));

        $groups = Group::withTrashed()
            ->with('owner')
            ->withCount(['memberships', 'mods', 'convoyProfiles'])
            ->when($search !== '', function ($q) use ($search): void {
                $term = '%'.addcslashes($search, '\\%_').'%';
                $q->where(fn ($sub) => $sub
                    ->where('name', 'like', $term)
                    ->orWhere('slug', 'like', $term)
                    ->orWhere('tag', 'like', $term));
            })
            ->when($filter === 'public', fn ($q) => $q->where('visibility', 'public')->whereNull('deleted_at'))
            ->when($filter === 'private', fn ($q) => $q->where('visibility', 'private')->whereNull('deleted_at'))
            ->when($filter === 'archived', fn ($q) => $q->onlyTrashed())
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $reports = ModerationReport::query()
            ->where('reportable_type', (new Group)->getMorphClass())
            ->with(['reporter', 'reviewer'])
            ->latest()
            ->limit(15)
            ->get();

        return view('admin.groups.index', [
            'groups' => $groups,
            'reports' => $reports,
            'search' => $search,
            'filter' => $filter,
        ]);
    }

    public function show(int $groupId): View
    {
        Gate::authorize('access-admin-dashboard');

        $group = Group::withTrashed()
            ->with(['owner', 'memberships.user', 'mods.game', 'convoyProfiles.game'])
            ->findOrFail($groupId);

        $reports = ModerationReport::query()
            ->where('reportable_type', $group->getMorphClass())
            ->where('reportable_id', $group->id)
            ->with(['reporter', 'reviewer'])
            ->latest()
            ->get();

        return view('admin.groups.show', [
            'group' => $group,
            'reports' => $reports,
        ]);
    }

    public function updateStatus(UpdateGroupAdminRequest $request, int $groupId): RedirectResponse
    {
        Gate::authorize('access-admin-dashboard');

        $group = Group::withTrashed()->findOrFail($groupId);

        /** @var User $actor */
        $actor = $request->user()->fresh();

        $this->moderation->updateGroup($actor, $group, $request->validated());

        return redirect()
            ->route('admin.groups.show', $group->id)
            ->with('status', 'Group settings updated.');
    }
}
