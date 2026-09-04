<?php

namespace App\Http\Requests\Api\V1\Guest;

use Illuminate\Foundation\Http\FormRequest;

class CreateGuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => [
                'required',
                'string',
                'min:2',
                'max:150',
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'address' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'nationality' => [
                'nullable',
                'string',
                'max:100',
            ],

            'id_type' => [
                'nullable',
                'in:national_id,passport',
            ],

            'id_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'gender' => [
                'nullable',
                'in:male,female,other',
            ],

            'date_of_birth' => [
                'nullable',
                'date',
                'before:today',
            ],

            'country' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Guest full name is required.',
            'full_name.min' => 'Guest name must be at least 2 characters.',
            'full_name.max' => 'Guest name cannot exceed 150 characters.',
            'email.email' => 'Please provide a valid email address.',
            'phone.max' => 'Phone number cannot exceed 30 characters.',
            'id_type.in' => 'Invalid ID type. Must be national_id or passport.',
            'gender.in' => 'Invalid gender. Must be male, female, or other.',
            'date_of_birth.before' => 'Date of birth must be in the past.',
        ];
    }
}
