<?php

namespace App\Services\Auth;

use App\Exceptions\LoginException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function login(string $email, string $password): array
    {
        $email = strtolower(trim($email));

        $user = User::where('email', $email)->first();

        if (! $user) {
            throw new LoginException(404, 'No account found with this email. Please register first.', [
                'email' => [
                    'No account found with this email.',
                ],
            ]);
        }

        if (! Hash::check($password, $user->password)) {
            throw new LoginException(401, 'Incorrect password. Please try again.', [
                'password' => [
                    'Incorrect password.',
                ],
            ]);
        }

        if ($user->status !== 'active') {
            throw new LoginException(403, 'Your account is inactive. Please contact support.', [
                'account' => [
                    'Your account is inactive.',
                ],
            ]);
        }

        $token = $user->createToken('customer-auth')->plainTextToken;

        return [
            'user' => $user->load('roles', 'guest'),
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    public function me(User $user): array
    {
        return [
            'user' => $user->load('roles', 'guest'),
        ];
    }
}
