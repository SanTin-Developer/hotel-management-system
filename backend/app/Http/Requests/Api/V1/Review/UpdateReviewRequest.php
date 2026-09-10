<?php

namespace App\Http\Requests\Api\V1\Review;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                'max:5',
            ],

            'comment' => [
                'sometimes',
                'nullable',
                'string',
                'max:5000',
            ],

            'comment_kh' => [
                'sometimes',
                'nullable',
                'string',
                'max:5000',
            ],

            'status' => [
                'sometimes',
                'required',
                'in:pending,approved,rejected',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.integer' => 'Rating must be a whole number.',
            'rating.min' => 'Rating must be at least 1.',
            'rating.max' => 'Rating cannot exceed 5.',
            'comment.max' => 'Comment cannot exceed 5000 characters.',
            'status.in' => 'Status must be pending, approved, or rejected.',
        ];
    }
}
