<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\GroupVisibility;
use App\Enums\MembershipStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\GroupResource;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GroupApiController extends Controller
{
    use AuthorizesRequests;

    /**
     * List all groups the authenticated user belongs to (active) plus public groups.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $groups = Group::query()
            ->where(function ($query) use ($user) {
                $query->where('visibility', GroupVisibility::Public->value)
                    ->orWhereHas('memberships', function ($membershipQuery) use ($user) {
                        $membershipQuery->where('user_id', $user->id)
                            ->where('status', MembershipStatus::Active->value);
                    });
            })
            ->with(['memberships' => fn ($q) => $q->where('user_id', $user->id)])
            ->orderBy('name')
            ->get();

        return GroupResource::collection($groups);
    }

    /**
     * Show details of a specific group (enforcing GroupPolicy::view).
     */
    public function show(Request $request, Group $group): GroupResource
    {
        $this->authorize('view', $group);

        return new GroupResource($group);
    }
}
