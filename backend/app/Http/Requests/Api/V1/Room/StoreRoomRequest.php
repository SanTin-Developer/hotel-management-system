<?php

namespace App\Http\Requests\Api\V1\Room;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_type_id' => [
                'required',
                'integer',
                'exists:room_types,id',
            ],

            'room_number' => [
                'required',
                'string',
                'max:20',
                'unique:rooms,room_number',
            ],

            'floor' => [
                'required',
                'integer',
                'min:1',
                'max:200',
            ],

            'status' => [
                'required',
                Rule::in([
                    'available',
                    'occupied',
                    'maintenance',
                    'cleaning',
                    'out_of_service',
                ]),
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'description_kh' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'room_type_id.required' => 'Room type is required.',
            'room_type_id.exists' => 'The selected room type does not exist.',

            'room_number.required' => 'Room number is required.',
            'room_number.unique' => 'This room number is already in use.',

            'floor.required' => 'Floor is required.',
            'floor.integer' => 'Floor must be a whole number.',
            'floor.min' => 'Floor must be at least 1.',

            'status.required' => 'Room status is required.',
            'status.in' => 'Invalid room status.',

            'description.max' => 'Room description cannot exceed 5000 characters.',
        ];
    }
}
