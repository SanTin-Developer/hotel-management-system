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
            'image' => [
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
            'image.required' => 'An image file is required.',
            'image.image' => 'The uploaded file must be an image.',
            'image.mimes' => 'Only jpeg, png, webp and gif images are allowed.',
            'image.max' => 'The image must not exceed 5 MB.',
        ];
    }
}
