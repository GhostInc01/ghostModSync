<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Admin;

use App\Enums\ModVersionStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateModVersionStatusRequest extends FormRequest
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
            'status' => ['required', Rule::enum(ModVersionStatus::class)],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
