<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use App\Enums\GroupRole;
use App\Enums\MembershipStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMembershipRequest extends FormRequest
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
            'role' => ['nullable', Rule::enum(GroupRole::class)],
            'status' => ['nullable', Rule::enum(MembershipStatus::class)],
        ];
    }
}
