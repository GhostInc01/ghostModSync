<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreGameVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isSuperAdmin();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'game_id' => ['required', 'integer', 'exists:games,id'],
            'version_branch' => ['required', 'string', 'max:32', 'regex:/^[0-9]+(\.[0-9]+)*(\.\*)?$/'],
            'is_current' => ['nullable', 'boolean'],
            'released_at' => ['nullable', 'date'],
        ];
    }
}
