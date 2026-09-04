<?php

namespace App\Http\Requests\Api\V1\Booking;

use Illuminate\Foundation\Http\FormRequest;

class RoomAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'check_in' => [
                'required',
                'date',
                'after_or_equal:today',
            ],

            'check_out' => [
                'required',
                'date',
                'after:check_in',
            ],
        ];
    }

    public function messages()
    {
        return [
            'check_in.required' => 'Check-in date is required.',
            'check_in.date' => 'Check-in must be a valid date.',
            'check_in.after_or_equal' => 'Check-in cannot be in the past.',

            'check_out.required' => 'Check-out date is required.',
            'check_out.date' => 'Check-out must be a valid date.',
            'check_out.after' => 'Check-out must be after check-in.',
        ];
    }
}
