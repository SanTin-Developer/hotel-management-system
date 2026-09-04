<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'verification_id' => [
                'required',
                'integer',
                'exists:registration_otps,id',
            ],

            'otp' => [
                'required',
                'digits:6',
            ],
        ];
    }
}
