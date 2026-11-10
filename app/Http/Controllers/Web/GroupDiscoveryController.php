<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\GroupVisibility;
use App\Enums\MembershipStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\DiscoverGroupsRequest;
use App\Models\Group;
use App\Models\GroupMembership;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Read-only directory of PUBLIC groups. Private groups are never listed.
 */
class GroupDiscoveryController extends Controller
{
    use AuthorizesRequests;

    public function index(DiscoverGroupsRequest $request): View
    {
        $this->authorize('viewAny', Group::class);

        $user = $request->user();
        $search = trim((string) $request->validated('q', ''));

        $groups = Group::query()
            ->where('visibility', GroupVisibility::Public->value)
            ->when($search !== '', function ($query) use ($search): void {
                // Escape LIKE wildcards so user input is matched literally.
                $term = '%'.addcslashes($search, '\\%_').'%';
                $query->where(fn ($q) => $q
                    ->where('name', 'like', $term)
                    ->orWhere('tag', 'like', $term)
                    ->orWhere('description', 'like', $term));
            })
            ->withCount(['memberships as active_members_count' => fn ($q) => $q->where('status', MembershipStatus::Active->value)])
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        // One query for the viewer's own relationship to the groups on this page.
        $myMemberships = GroupMembership::query()
            ->where('user_id', $user->id)
            ->whereIn('group_id', $groups->pluck('id'))
            ->get()
            ->keyBy('group_id');

        return view('groups.discover', [
            'groups' => $groups,
            'myMemberships' => $myMemberships,
            'search' => Str::limit($search, 100, ''),
        ]);
    }
}
