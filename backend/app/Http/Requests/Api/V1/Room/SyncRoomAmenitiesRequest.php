<?php

namespace App\Http\Requests\Api\V1\Room;

use Illuminate\Foundation\Http\FormRequest;

class SyncRoomAmenitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amenity_ids' => [
                'required',
                'array',
                'max:50',
            ],

            'amenity_ids.*' => [
                'integer',
                'distinct',
                'exists:amenities,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'amenity_ids.required' => 'Amenity IDs are required.',
            'amenity_ids.array' => 'Amenity IDs must be an array.',
            'amenity_ids.max' => 'A room cannot have more than 50 amenities.',
            'amenity_ids.*.integer' => 'Each amenity ID must be an integer.',
            'amenity_ids.*.distinct' => 'Duplicate amenity IDs are not allowed.',
            'amenity_ids.*.exists' => 'One or more selected amenities do not exist.',
        ];
    }
}
