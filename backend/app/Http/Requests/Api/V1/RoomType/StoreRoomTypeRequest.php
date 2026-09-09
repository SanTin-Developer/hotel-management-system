<?php

namespace App\Http\Requests\Api\V1\RoomType;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomTypeRequest extends FormRequest
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
                'unique:room_types,name',
            ],

            'name_kh' => [
                'nullable',
                'string',
                'max:100',
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

            'capacity' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],

            'base_price' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],

            'size' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
            ],

            'bed_type' => [
                'nullable',
                'string',
                'max:100',
            ],

            'image_url' => [
                'nullable',
                'url',
                'max:500',
            ],

            'status' => [
                'required',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Room type name is required.',
            'name.unique' => 'A room type with this name already exists.',

            'capacity.required' => 'Room capacity is required.',
            'capacity.integer' => 'Room capacity must be a whole number.',
            'capacity.min' => 'Room capacity must be at least 1.',

            'base_price.required' => 'Base price is required.',
            'base_price.numeric' => 'Base price must be a valid number.',
            'base_price.min' => 'Base price cannot be negative.',

            'size.numeric' => 'Room size must be a valid number.',
            'size.min' => 'Room size cannot be negative.',

            'image_url.url' => 'Image URL must be a valid URL.',

            'status.required' => 'Room type status is required.',
            'status.in' => 'Room type status must be active or inactive.',
        ];
    }
}
