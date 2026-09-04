<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PasswordController extends Controller
{
    public function __construct(
        private readonly PasswordResetService $passwordResetService
    ) {}

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
            ],
        ]);

        $result = $this->passwordResetService->sendResetLink(
            $request->input('email')
        );

        return response()->json($result);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
            ],

            'otp' => [
                'required',
                'digits:6',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $result = $this->passwordResetService->resetPassword(
            $request->input('email'),
            $request->input('otp'),
            $request->input('password'),
            $request->input('password_confirmation')
        );

        return response()->json($result);
    }
}
