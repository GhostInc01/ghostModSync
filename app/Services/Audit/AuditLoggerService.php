<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLoggerService
{
    public function __construct(
        private readonly ?Request $request = null,
    ) {}

    /**
     * Record an immutable audit log entry.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function log(
        string $event,
        Model $auditable,
        ?User $actor = null,
        ?int $groupId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?Device $device = null,
    ): AuditLog {
        $req = $this->request ?? request();
        $resolvedUser = $actor ?? $req?->user();

        return AuditLog::query()->create([
            'group_id' => $groupId,
            'user_id' => $resolvedUser?->getKey(),
            'device_id' => $device?->getKey(),
            'event' => $event,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => (int) $auditable->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $req?->ip(),
            'user_agent' => $req?->userAgent() ? substr((string) $req->userAgent(), 0, 500) : null,
            'created_at' => now(),
        ]);
    }
}
