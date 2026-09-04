<?php

namespace App\Http\Requests\Api\V1\Booking;

use App\Models\Guest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'guest_id' => [
                'required',
                'integer',
                'exists:guests,id',
            ],

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

            'adults' => [
                'required',
                'integer',
                'min:1',
                'max:20',
            ],

            'children' => [
                'nullable',
                'integer',
                'min:0',
                'max:20',
            ],

            'room_ids' => [
                'required',
                'array',
                'min:1',
                'max:20',
            ],

            'room_ids.*' => [
                'integer',
                'distinct',
                'exists:rooms,id',
            ],

            'coupon_id' => [
                'nullable',
                'integer',
                'exists:coupons,id',
            ],

            'special_request' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'guest_id.required' => 'Guest is required.',
            'guest_id.exists' => 'The selected guest does not exist.',
            'check_in.required' => 'Check-in date is required.',
            'check_in.date' => 'Check-in must be a valid date.',
            'check_in.after_or_equal' => 'Check-in cannot be in the past.',

            'check_out.required' => 'Check-out date is required.',
            'check_out.date' => 'Check-out must be a valid date.',
            'check_out.after' => 'Check-out must be after check-in.',

            'adults.required' => 'Number of adults is required.',
            'adults.integer' => 'Adults must be a whole number.',
            'adults.min' => 'At least one adult is required.',

            'children.integer' => 'Children must be a whole number.',
            'children.min' => 'Children cannot be negative.',

            'room_ids.required' => 'At least one room must be selected.',
            'room_ids.array' => 'Room IDs must be an array.',
            'room_ids.min' => 'At least one room must be selected.',
            'room_ids.*.integer' => 'Each room ID must be an integer.',
            'room_ids.*.distinct' => 'Duplicate room IDs are not allowed.',
            'room_ids.*.exists' => 'One or more selected rooms do not exist.',

            'coupon_id.exists' => 'The selected coupon does not exist.',

            'special_request.max' => 'Special request cannot exceed 2000 characters.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $user = $this->user();

                if (! $user) {
                    return;
                }

                if ($user->hasAnyRole(['admin', 'manager'])) {
                    return;
                }

                $guestId = $this->input('guest_id');

                if (! $guestId) {
                    return;
                }

                $ownsGuest = Guest::query()
                    ->whereKey($guestId)
                    ->where('email', $user->email)
                    ->exists();

                if (! $ownsGuest) {
                    $validator->errors()->add(
                        'guest_id',
                        'You can only create a booking for your own guest profile.'
                    );
                }
            },
        ];
    }
}
