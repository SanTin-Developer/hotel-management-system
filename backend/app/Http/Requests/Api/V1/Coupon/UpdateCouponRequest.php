<?php

namespace App\Http\Requests\Api\V1\Coupon;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $couponId = $this->route('coupon');

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('coupons', 'code')->ignore($couponId),
            ],

            'discount_type' => [
                'sometimes',
                'required',
                Rule::in(['percentage', 'fixed']),
            ],

            'discount_value' => [
                'sometimes',
                'required',
                'numeric',
                'min:0.01',
                'max:9999999999.99',
            ],

            'min_amount' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],

            'start_date' => [
                'sometimes',
                'required',
                'date',
            ],

            'end_date' => [
                'sometimes',
                'required',
                'date',
                'after:start_date',
            ],

            'usage_limit' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
            ],

            'status' => [
                'sometimes',
                'required',
                Rule::in(['active', 'inactive']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'A coupon with this code already exists.',
            'code.max' => 'Coupon code cannot exceed 50 characters.',
            'discount_type.in' => 'Discount type must be percentage or fixed.',
            'discount_value.numeric' => 'Discount value must be a valid number.',
            'discount_value.min' => 'Discount value must be greater than zero.',
            'end_date.after' => 'End date must be after start date.',
            'usage_limit.min' => 'Usage limit must be at least 1.',
            'status.in' => 'Status must be active or inactive.',
        ];
    }
}
