<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use App\Enums\GroupRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGroupInvitationRequest extends FormRequest
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
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'role' => ['required', Rule::enum(GroupRole::class)],
            'max_uses' => ['nullable', 'integer', 'min:1', 'max:500'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:30'],
        ];
    }
}
