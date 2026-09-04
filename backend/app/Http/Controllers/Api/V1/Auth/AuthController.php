<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\ResendOtpRequest;
use App\Http\Requests\Api\V1\Auth\VerifyOtpRequest;
use App\Models\User;
use App\Services\Auth\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Register a new user and generate OTP
    public function register(
        RegisterRequest $request,
        RegistrationService $registrationService
    ): JsonResponse {

        $registration = $registrationService->createRegistration(
            $request->validated()
        );

        return response()->json([
            'message' => 'OTP generated. Please Verify your email with the OTP sent to your email address.',
            'verification_id' => $registration->id,
            'expires_at' => $registration->expires_at,
        ], 201);
    }

    // Verify the OTP and create a new user account
    public function verifyOtp(
        VerifyOtpRequest $request,
        RegistrationService $registrationService
    ): JsonResponse {
        $result = $registrationService->verifyOtp(
            $request->integer('verification_id'),
            $request->string('otp')->toString()
        );

        return response()->json([
            'message' => 'Registration completed successfully.',
            'user' => $result['user'],
            'token' => $result['token'],
        ], 201);
    }

    // Resend the OTP
    public function resendOtp(
        ResendOtpRequest $request,
        RegistrationService $registrationService
    ): JsonResponse {
        $registration = $registrationService->resendOtp(
            $request->integer('verification_id')
        );

        return response()->json([
            'message' => 'A new OTP has been sent to your email address.',
            'verification_id' => $registration->id,
            'expires_at' => $registration->expires_at,
        ]);
    }

    // Login
    public function login(LoginRequest $request): JsonResponse
    {
        $email = strtolower(trim($request->validated('email')));
        $password = $request->validated('password');

        $user = User::where('email', $email)->first();

        // Email does not exist
        if (! $user) {
            return response()->json([
                'message' => 'No account found with this email. Please register first.',
                'errors' => [
                    'email' => [
                        'No account found with this email.',
                    ],
                ],
            ], 404);
        }

        // Email exists but password is incorrect
        if (! Hash::check($password, $user->password)) {
            return response()->json([
                'message' => 'Incorrect password. Please try again.',
                'errors' => [
                    'password' => [
                        'Incorrect password.',
                    ],
                ],
            ], 401);
        }

        // Account exists but is inactive
        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'Your account is inactive. Please contact support.',
                'errors' => [
                    'account' => [
                        'Your account is inactive.',
                    ],
                ],
            ], 403);
        }

        $token = $user->createToken('customer-auth')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user->load('roles'),
            'token' => $token,
        ]);
    }

    // Me
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles');

        return response()->json([
            'message' => 'Authenticated user retrieved successfully.',
            'user' => $user,
        ]);
    }

    // Logout
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }
}
