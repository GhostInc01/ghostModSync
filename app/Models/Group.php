<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Enums\MembershipStatus;
use App\Enums\SyncPolicyMode;
use Database\Factories\GroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    /** @use HasFactory<GroupFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'require_join_approval' => true,
        'require_mod_approval' => true,
        'storage_quota_bytes' => 53687091200,
    ];

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'tag',
        'description',
        'visibility',
        'sync_policy',
        'require_join_approval',
        'require_mod_approval',
        'logo_path',
        'banner_path',
        'storage_quota_bytes',
    ];

    protected function casts(): array
    {
        return [
            'visibility' => GroupVisibility::class,
            'sync_policy' => SyncPolicyMode::class,
            'require_join_approval' => 'boolean',
            'require_mod_approval' => 'boolean',
            'storage_quota_bytes' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isPublic(): bool
    {
        return $this->visibility === GroupVisibility::Public;
    }

    public function isPrivate(): bool
    {
        return $this->visibility === GroupVisibility::Private;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<GroupMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(GroupMembership::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_memberships')
            ->withPivot(['id', 'role', 'status', 'invited_by', 'approved_by', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<GroupInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(GroupInvitation::class);
    }

    /**
     * @return HasMany<GroupJoinRequest, $this>
     */
    public function joinRequests(): HasMany
    {
        return $this->hasMany(GroupJoinRequest::class);
    }

    /**
     * @return HasMany<GroupModApproval, $this>
     */
    public function modApprovals(): HasMany
    {
        return $this->hasMany(GroupModApproval::class);
    }

    /**
     * @return HasMany<Mod, $this>
     */
    public function mods(): HasMany
    {
        return $this->hasMany(Mod::class);
    }

    /**
     * @return HasMany<ConvoyProfile, $this>
     */
    public function convoyProfiles(): HasMany
    {
        return $this->hasMany(ConvoyProfile::class);
    }

    public function membershipFor(User $user): ?GroupMembership
    {
        if ($this->relationLoaded('memberships')) {
            /** @var GroupMembership|null $loaded */
            $loaded = $this->memberships->firstWhere('user_id', $user->id);

            return $loaded;
        }

        return $this->memberships()
            ->where('user_id', $user->id)
            ->first();
    }

    public function activeRoleFor(User $user): ?GroupRole
    {
        if ($this->owner_id === $user->id) {
            return GroupRole::Owner;
        }

        $membership = $this->membershipFor($user);

        if ($membership === null || $membership->status !== MembershipStatus::Active) {
            return null;
        }

        return $membership->role;
    }
}
