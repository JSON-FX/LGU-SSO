<?php

namespace App\Http\Requests\Employee;

use App\Enums\CivilStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employee = $this->route('employee');

        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'birthday' => ['sometimes', 'date', 'before:today'],
            'civil_status' => ['sometimes', Rule::enum(CivilStatus::class)],
            'province_code' => ['nullable', 'string', 'exists:psgc_provinces,code'],
            'city_code' => ['nullable', 'string', 'exists:psgc_cities,code'],
            'barangay_code' => ['nullable', 'string', 'exists:psgc_barangays,code'],
            'residence' => ['sometimes', 'string', 'max:500'],
            'block_number' => ['nullable', 'string', 'max:50'],
            'building_floor' => ['nullable', 'string', 'max:50'],
            'house_number' => ['nullable', 'string', 'max:50'],
            'nationality' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'email', Rule::unique('employees', 'email')->ignore($employee)],
            'password' => ['sometimes', Password::defaults()],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
