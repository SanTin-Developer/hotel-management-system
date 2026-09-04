<?php

namespace App\Http\Requests\Api\V1\Review;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexReviewRequest extends FormRequest
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
                Rule::in(['pending', 'approved', 'rejected']),
            ],

            'rating' => [
                'nullable',
                'integer',
                'min:1',
                'max:5',
            ],

            'guest_id' => [
                'nullable',
                'integer',
                'exists:guests,id',
            ],

            'booking_id' => [
                'nullable',
                'integer',
                'exists:bookings,id',
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
