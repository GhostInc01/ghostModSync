<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SyncConvoyProfileModsRequest extends FormRequest
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
            'mods' => ['present', 'array'],
            'mods.*.mod_version_id' => ['required', 'integer', 'exists:mod_versions,id'],
            'mods.*.is_required' => ['nullable', 'boolean'],
            'mods.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
