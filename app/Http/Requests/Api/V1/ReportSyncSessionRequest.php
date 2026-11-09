<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\SyncItemAction;
use App\Enums\SyncStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportSyncSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'device_uuid' => ['required', 'uuid'],
            'reported_manifest_hash' => ['nullable', 'string', 'size:64', 'regex:/^[a-fA-F0-9]{64}$/'],
            'status' => ['required', Rule::enum(SyncStatus::class)],
            'missing_mods_count' => ['nullable', 'integer', 'min:0'],
            'mismatched_mods_count' => ['nullable', 'integer', 'min:0'],
            'extra_local_mods_count' => ['nullable', 'integer', 'min:0'],
            'bytes_downloaded' => ['nullable', 'integer', 'min:0'],
            'duration_ms' => ['nullable', 'integer', 'min:0'],
            'error_message' => ['nullable', 'string', 'max:2000'],
            'items' => ['nullable', 'array'],
            'items.*.mod_version_id' => ['required_with:items', 'integer', 'exists:mod_versions,id'],
            'items.*.action_taken' => ['required_with:items', Rule::enum(SyncItemAction::class)],
            'items.*.status' => ['required_with:items', Rule::enum(SyncStatus::class)],
            'items.*.local_sha256_hash' => ['nullable', 'string', 'size:64', 'regex:/^[a-fA-F0-9]{64}$/'],
            'items.*.bytes_downloaded' => ['nullable', 'integer', 'min:0'],
            'items.*.error_detail' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
