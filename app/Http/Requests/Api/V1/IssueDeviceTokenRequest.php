<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IssueDeviceTokenRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'device_uuid' => ['required', 'uuid'],
            'device_name' => ['required', 'string', 'max:120'],
            'os_version' => ['nullable', 'string', 'max:100'],
            'client_version' => ['nullable', 'string', 'max:40'],
            'ets2_mod_path' => ['nullable', 'string', 'max:500'],
            'ats_mod_path' => ['nullable', 'string', 'max:500'],
        ];
    }
}
