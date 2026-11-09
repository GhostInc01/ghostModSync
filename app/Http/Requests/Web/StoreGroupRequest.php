<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use App\Enums\GroupVisibility;
use App\Enums\SyncPolicyMode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->hasVerifiedEmail();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'alpha_dash', Rule::unique('groups', 'slug')],
            'tag' => ['nullable', 'string', 'max:16'],
            'description' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', Rule::enum(GroupVisibility::class)],
            'sync_policy' => ['required', Rule::enum(SyncPolicyMode::class)],
            'require_join_approval' => ['nullable', 'boolean'],
            'require_mod_approval' => ['nullable', 'boolean'],
            'logo_path' => ['nullable', 'string', 'max:255'],
            'banner_path' => ['nullable', 'string', 'max:255'],
        ];
    }
}
