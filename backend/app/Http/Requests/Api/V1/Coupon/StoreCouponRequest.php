<?php

namespace App\Http\Requests\Api\V1\Coupon;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'unique:coupons,code',
            ],

            'discount_type' => [
                'required',
                Rule::in(['percentage', 'fixed']),
            ],

            'discount_value' => [
                'required',
                'numeric',
                'min:0.01',
                'max:9999999999.99',
            ],

            'min_amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],

            'start_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'required',
                'date',
                'after:start_date',
            ],

            'usage_limit' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'status' => [
                'required',
                Rule::in(['active', 'inactive']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Coupon code is required.',
            'code.unique' => 'A coupon with this code already exists.',
            'code.max' => 'Coupon code cannot exceed 50 characters.',
            'discount_type.required' => 'Discount type is required.',
            'discount_type.in' => 'Discount type must be percentage or fixed.',
            'discount_value.required' => 'Discount value is required.',
            'discount_value.numeric' => 'Discount value must be a valid number.',
            'discount_value.min' => 'Discount value must be greater than zero.',
            'start_date.required' => 'Start date is required.',
            'start_date.date' => 'Start date must be a valid date.',
            'end_date.required' => 'End date is required.',
            'end_date.after' => 'End date must be after start date.',
            'usage_limit.min' => 'Usage limit must be at least 1.',
            'status.required' => 'Status is required.',
            'status.in' => 'Status must be active or inactive.',
        ];
    }
}
