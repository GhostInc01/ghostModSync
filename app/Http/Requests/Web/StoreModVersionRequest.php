<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreModVersionRequest extends FormRequest
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
            'version_number' => ['required', 'string', 'max:50'],
            'min_game_version' => ['nullable', 'string', 'max:32'],
            'max_game_version' => ['nullable', 'string', 'max:32'],
            'archive_filename' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9._\-]+\.(scs|zip)$/i'],
            'file_size_bytes' => ['nullable', 'integer', 'min:1'],
            'sha256_hash' => ['nullable', 'string', 'size:64', 'regex:/^[a-fA-F0-9]{64}$/'],
            'steam_published_file_id' => ['nullable', 'string', 'regex:/^\d{5,25}$/'],
            'changelog' => ['nullable', 'string', 'max:3000'],
            'required_dlc_codes' => ['nullable', 'array'],
            'required_dlc_codes.*' => ['string', 'max:50'],
        ];
    }
}
