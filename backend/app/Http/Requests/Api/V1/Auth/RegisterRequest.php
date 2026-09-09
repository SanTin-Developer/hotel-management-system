<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'An account with this email already exists. Please log in instead.',
        ];
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

            'country' => [
                'required',
                'string',
                'max:100',
            ],

            'nationality' => [
                'required',
                'string',
                'max:100',
            ],

            'date_of_birth' => [
                'required',
                'date',
                'before:today',
            ],

            'address' => [
                'required',
                'string',
                'max:255',
            ],

            'id_type' => [
                'required',
                'in:national_id,passport',
            ],

            'id_number' => [
                'required',
                'string',
                'max:100',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'phone' => [
                'required',
                'string',
                'max:30',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],

            'photo' => [
                'nullable',
                'image',
                'mimes:jpeg,png,webp,gif',
                'max:5120',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $country = strtolower(trim($this->input('country')));
                $idType = $this->input('id_type');

                if ($country === 'cambodia' && $idType !== 'national_id') {
                    $validator->errors()->add(
                        'id_type',
                        'Customers from Cambodia must use a national ID.'
                    );
                }

                if ($country !== 'cambodia' && $idType !== 'passport') {
                    $validator->errors()->add(
                        'id_type',
                        'Customers from other countries must use a passport.'
                    );
                }
            },
        ];
    }
}
