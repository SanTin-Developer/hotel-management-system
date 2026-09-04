<?php

namespace App\Http\Requests\Api\V1\Staff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $staff = $this->route('staff');
        $staffId = is_object($staff) ? $staff->id : $staff;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:150',
            ],

            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore(
                    $this->route('staff')?->user_id ?? null
                ),
            ],

            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],

            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:30',
            ],

            'role' => [
                'sometimes',
                'nullable',
                'string',
                'in:admin,manager,staff',
            ],

            'employee_id' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('staff', 'employee_id')->ignore($staffId),
            ],

            'position' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],

            'hire_date' => [
                'sometimes',
                'required',
                'date',
                'before_or_equal:today',
            ],

            'status' => [
                'sometimes',
                'nullable',
                'string',
                'in:active,inactive',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.min' => 'Name must be at least 2 characters.',
            'email.unique' => 'A user with this email already exists.',
            'password.min' => 'Password must be at least 8 characters.',
            'employee_id.unique' => 'This employee ID is already in use.',
            'hire_date.before_or_equal' => 'Hire date cannot be in the future.',
        ];
    }
}
