<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\ResendOtpRequest;
use App\Http\Requests\Api\V1\Auth\UpdateProfileRequest;
use App\Http\Requests\Api\V1\Auth\UploadProfilePhotoRequest;
use App\Http\Requests\Api\V1\Auth\VerifyOtpRequest;
use App\Services\Auth\AuthService;
use App\Services\Auth\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly RegistrationService $registrationService
    ) {}

    public function register(
        RegisterRequest $request
    ): JsonResponse {
        $registration = $this->registrationService->createRegistration(
            $request->validated()
        );

        return response()->json([
            'message' => 'OTP generated. Please Verify your email with the OTP sent to your email address.',
            'verification_id' => $registration->id,
            'expires_at' => $registration->expires_at,
        ], 201);
    }

    public function verifyOtp(
        VerifyOtpRequest $request
    ): JsonResponse {
        $result = $this->registrationService->verifyOtp(
            $request->integer('verification_id'),
            $request->string('otp')->toString()
        );

        return response()->json([
            'message' => 'Registration completed successfully.',
            'user' => $result['user'],
            'token' => $result['token'],
        ], 201);
    }

    public function resendOtp(
        ResendOtpRequest $request
    ): JsonResponse {
        $registration = $this->registrationService->resendOtp(
            $request->integer('verification_id')
        );

        return response()->json([
            'message' => 'A new OTP has been sent to your email address.',
            'verification_id' => $registration->id,
            'expires_at' => $registration->expires_at,
        ]);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->validated('email'),
            $request->validated('password')
        );

        return response()->json([
            'message' => 'Login successful.',
            'user' => $result['user'],
            'token' => $result['token'],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $result = $this->authService->me($request->user());

        return response()->json([
            'message' => 'Authenticated user retrieved successfully.',
            'user' => $result['user'],
        ]);
    }

    public function updateMe(
        UpdateProfileRequest $request
    ): JsonResponse {
        $result = $this->authService->updateProfile(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $result['user'],
        ]);
    }

    public function uploadMePhoto(
        UploadProfilePhotoRequest $request
    ): JsonResponse {
        $result = $this->authService->uploadPhoto(
            $request->user(),
            $request->file('photo')
        );

        return response()->json([
            'message' => 'Photo updated successfully.',
            'user' => $result['user'],
        ]);
    }

    public function removeMePhoto(Request $request): JsonResponse
    {
        $result = $this->authService->removePhoto(
            $request->user()
        );

        return response()->json([
            'message' => 'Photo removed successfully.',
            'user' => $result['user'],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }
}
