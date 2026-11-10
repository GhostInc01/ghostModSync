<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGroupAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isPlatformAdmin();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['archive', 'restore', 'update_quota'])],
            'storage_quota_gb' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
