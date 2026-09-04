<?php

namespace App\Http\Requests\Api\V1\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_id' => [
                'required',
                'integer',
                'exists:bookings,id',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:9999999999.99',
            ],

            'payment_method' => [
                'required',
                'string',
                Rule::in([
                    'cash',
                    'card',
                    'bank_transfer',
                    'online',
                ]),
            ],

            'transaction_id' => [
                'nullable',
                'string',
                'max:150',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'booking_id.required' => 'Booking is required.',
            'booking_id.exists' => 'The selected booking does not exist.',

            'amount.required' => 'Payment amount is required.',
            'amount.numeric' => 'Payment amount must be a valid number.',
            'amount.min' => 'Payment amount must be greater than zero.',

            'payment_method.required' => 'Payment method is required.',
            'payment_method.in' => 'Invalid payment method.',

            'transaction_id.max' => 'Transaction ID cannot exceed 150 characters.',
        ];
    }
}
