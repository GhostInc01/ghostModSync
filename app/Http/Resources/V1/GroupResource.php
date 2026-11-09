<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Group
 */
class GroupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'tag' => $this->tag,
            'description' => $this->description,
            'visibility' => $this->visibility->value,
            'sync_policy' => $this->sync_policy->value,
            'require_join_approval' => $this->require_join_approval,
            'my_role' => $user ? $this->activeRoleFor($user)?->value : null,
            'created_at' => $this->created_at?->toIso8601ZuluString(),
        ];
    }
}
