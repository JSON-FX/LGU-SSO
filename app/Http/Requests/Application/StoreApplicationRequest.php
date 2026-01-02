<?php

namespace App\Http\Requests\Application;

use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'redirect_uris' => ['nullable', 'array'],
            'redirect_uris.*' => ['url'],
            'rate_limit_per_minute' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ];
    }
}
