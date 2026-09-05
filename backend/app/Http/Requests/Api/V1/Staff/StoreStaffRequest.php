<?php

namespace App\Http\Requests\Api\V1\Staff;

use Illuminate\Foundation\Http\FormRequest;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:150',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'role' => [
                'nullable',
                'string',
                'in:admin,manager,staff',
            ],

            'employee_id' => [
                'nullable',
                'string',
                'max:50',
                'unique:staff,employee_id',
            ],

            'position' => [
                'required',
                'string',
                'max:100',
            ],

            'hire_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],

            'status' => [
                'nullable',
                'string',
                'in:active,inactive',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'name.min' => 'Name must be at least 2 characters.',
            'email.required' => 'Email is required.',
            'email.unique' => 'A user with this email already exists.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'employee_id.unique' => 'This employee ID is already in use.',
            'position.required' => 'Position is required.',
            'hire_date.required' => 'Hire date is required.',
            'hire_date.before_or_equal' => 'Hire date cannot be in the future.',
        ];
    }
}
