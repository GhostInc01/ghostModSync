<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use App\Enums\GameCode;
use App\Enums\SyncPolicyMode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConvoyProfileRequest extends FormRequest
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
            'game_code' => ['required', Rule::enum(GameCode::class)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'target_game_version' => ['required', 'string', 'max:32'],
            'sync_policy_override' => ['nullable', Rule::enum(SyncPolicyMode::class)],
            'is_default' => ['nullable', 'boolean'],
            'scheduled_convoy_at' => ['nullable', 'date'],
            'mods' => ['nullable', 'array'],
            'mods.*.mod_version_id' => ['required', 'integer', 'exists:mod_versions,id'],
            'mods.*.is_required' => ['nullable', 'boolean'],
            'mods.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
