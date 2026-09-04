<?php

namespace App\Http\Requests\Api\V1\Room;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAmenityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $amenity = $this->route('amenity');
        $amenityId = is_object($amenity) ? $amenity->id : $amenity;

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('amenities', 'name')->ignore($amenityId),
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'icon' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Amenity name is required.',
            'name.unique' => 'An amenity with this name already exists.',
            'description.max' => 'Amenity description cannot exceed 1000 characters.',
            'icon.max' => 'Amenity icon cannot exceed 100 characters.',
        ];
    }
}
