<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => [
                'sometimes',
                'string',
                'min:2',
                'max:150',
            ],

            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:30',
            ],

            'country' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'id_type' => [
                'sometimes',
                'nullable',
                'in:national_id,passport',
            ],

            'id_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'address' => [
                'sometimes',
                'nullable',
                'string',
                'max:5000',
            ],

            'nationality' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'gender' => [
                'sometimes',
                'nullable',
                'in:male,female,other',
            ],

            'date_of_birth' => [
                'sometimes',
                'nullable',
                'date',
                'before:today',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.min' => 'Full name must be at least 2 characters.',
            'full_name.max' => 'Full name cannot exceed 150 characters.',
            'id_type.in' => 'Invalid ID type. Must be national_id or passport.',
            'gender.in' => 'Invalid gender. Must be male, female, or other.',
            'date_of_birth.before' => 'Date of birth must be in the past.',
        ];
    }
}