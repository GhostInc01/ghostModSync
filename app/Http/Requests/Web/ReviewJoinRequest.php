<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use App\Enums\JoinRequestStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewJoinRequest extends FormRequest
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
            'status' => [
                'required',
                Rule::in([JoinRequestStatus::Approved->value, JoinRequestStatus::Rejected->value]),
            ],
            'review_notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
