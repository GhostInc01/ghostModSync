<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use App\Enums\DependencyType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreModDependencyRequest extends FormRequest
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
            'related_mod_id' => ['required', 'integer', 'exists:mods,id'],
            'related_mod_version_id' => ['nullable', 'integer', 'exists:mod_versions,id'],
            'dependency_type' => ['required', Rule::enum(DependencyType::class)],
        ];
    }
}
