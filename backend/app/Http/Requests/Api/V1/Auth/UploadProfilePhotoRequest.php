<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UploadProfilePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photo' => [
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
            'photo.required' => 'A photo file is required.',
            'photo.image' => 'The uploaded file must be an image.',
            'photo.mimes' => 'Only jpeg, png, webp and gif images are allowed.',
            'photo.max' => 'The photo must not exceed 5 MB.',
        ];
    }
}