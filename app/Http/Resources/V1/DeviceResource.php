<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Device
 */
class DeviceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_uuid' => $this->device_uuid,
            'device_name' => $this->device_name,
            'os_version' => $this->os_version,
            'client_version' => $this->client_version,
            'ets2_mod_path' => $this->ets2_mod_path,
            'ats_mod_path' => $this->ats_mod_path,
            'status' => $this->isRevoked() ? 'revoked' : 'active',
            'is_revoked' => $this->isRevoked(),
            'last_seen_at' => $this->last_seen_at?->toIso8601ZuluString(),
            'revoked_at' => $this->revoked_at?->toIso8601ZuluString(),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
        ];
    }
}
