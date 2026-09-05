<?php

namespace App\Http\Requests\Api\V1\Room;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeRoomStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([
                    'available',
                    'occupied',
                    'maintenance',
                    'cleaning',
                    'out_of_service',
                ]),
            ],

            'note' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Room status is required.',
            'status.in' => 'Invalid room status.',
            'note.max' => 'Note cannot exceed 500 characters.',
        ];
    }
}
