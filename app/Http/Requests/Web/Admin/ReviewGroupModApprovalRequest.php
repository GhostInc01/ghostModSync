<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Admin;

use App\Enums\ModApprovalStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewGroupModApprovalRequest extends FormRequest
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
            'status' => ['required', Rule::enum(ModApprovalStatus::class)],
            'review_notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
