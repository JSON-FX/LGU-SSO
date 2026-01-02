<?php

namespace App\Http\Requests\Employee;

use App\Enums\AppRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GrantAppAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'application_uuid' => ['required', 'uuid', 'exists:applications,uuid'],
            'role' => ['required', Rule::enum(AppRole::class)],
        ];
    }
}
