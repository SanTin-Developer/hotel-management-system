<?php

namespace App\Http\Requests\Api\V1\Room;

use Illuminate\Foundation\Http\FormRequest;

class StoreAmenityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                'unique:amenities,name',
            ],

            'name_kh' => [
                'nullable',
                'string',
                'max:100',
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'description_kh' => [
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
