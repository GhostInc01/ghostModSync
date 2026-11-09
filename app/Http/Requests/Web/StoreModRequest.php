<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use App\Enums\GameCode;
use App\Enums\ModSourceType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreModRequest extends FormRequest
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
            'category' => ['nullable', 'string', 'max:50'],
            'author_name' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'source_type' => ['required', Rule::enum(ModSourceType::class)],
        ];
    }
}
