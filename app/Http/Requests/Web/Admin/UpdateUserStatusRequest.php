<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Admin;

use App\Enums\PlatformRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null || ! $user->isPlatformAdmin()) {
            return false;
        }

        $target = $this->route('user');
        if ($target instanceof User && $target->isSuperAdmin() && ! $user->isSuperAdmin()) {
            return false;
        }

        if ($this->input('action') === 'update_role') {
            return $user->isSuperAdmin();
        }

        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['suspend', 'reactivate', 'update_role'])],
            'reason' => ['nullable', 'string', 'max:500'],
            'platform_role' => [
                'nullable',
                Rule::in(['none', PlatformRole::SuperAdmin->value, PlatformRole::PlatformAdmin->value]),
            ],
        ];
    }
}
