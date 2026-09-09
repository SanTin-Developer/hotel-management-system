<?php

namespace App\Http\Requests\Api\V1\RoomType;

use Illuminate\Foundation\Http\FormRequest;

class UploadRoomTypeImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'manager'])
            || $this->user()?->can('room-types.update') === true;
    }

    public function rules(): array
    {
        return [
            'images' => [
                'required',
                'array',
                'max:10',
            ],
            'images.*' => [
                'required',
                'image',
                'mimes:jpeg,png,webp,gif',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'images.required' => 'At least one image is required.',
            'images.array' => 'Images must be an array.',
            'images.max' => 'You can upload up to 10 images at once.',
            'images.*.required' => 'Each image file is required.',
            'images.*.image' => 'Each uploaded file must be an image.',
            'images.*.mimes' => 'Only jpeg, png, webp and gif images are allowed.',
            'images.*.max' => 'Each image must not exceed 5 MB.',
        ];
    }
}
