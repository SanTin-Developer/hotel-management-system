<?php

namespace App\Http\Requests\Api\V1\Booking;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'pending',
                    'confirmed',
                    'cancelled',
                    'completed',
                ]),
            ],

            'guest_id' => [
                'nullable',
                'integer',
                'exists:guests,id',
            ],

            'check_in' => [
                'nullable',
                'date',
            ],

            'check_out' => [
                'nullable',
                'date',
                'after_or_equal:check_in',
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }
}
