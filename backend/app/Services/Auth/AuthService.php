<?php

namespace App\Services\Auth;

use App\Exceptions\LoginException;
use App\Models\Guest;
use App\Models\User;
use App\Services\Media\CloudinaryService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        private readonly CloudinaryService $cloudinary
    ) {}
    public function login(string $identifier, string $password): array
    {
        $identifier = strtolower(trim($identifier));

        $user = User::where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->first();

        if (! $user) {
            throw new LoginException(404, 'No account found with this email or phone. Please register first.', [
                'email' => [
                    'No account found with this email or phone.',
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

        // Ensure the user has a guest profile so the customer portal works
        // even when the account was originally created via the admin portal.
        if (! $user->guest()->exists()) {
            Guest::create([
                'full_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'country' => null,
                'id_type' => null,
                'id_number' => null,
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

    public function updateProfile(User $user, array $data): array
    {
        return DB::transaction(function () use ($user, $data) {
            if (array_key_exists('full_name', $data) && $data['full_name'] !== null) {
                $user->name = $data['full_name'];
            }

            if (array_key_exists('phone', $data)) {
                $user->phone = $data['phone'];
            }

            $user->save();

            $guest = $user->guest()->first();

            if ($guest) {
                $guest->update(array_intersect_key($data, array_flip([
                    'full_name',
                    'phone',
                    'country',
                    'id_type',
                    'id_number',
                    'address',
                    'nationality',
                    'gender',
                    'date_of_birth',
                ])));
            }

            return [
                'user' => $user->fresh()->load('roles', 'guest'),
            ];
        });
    }

    public function me(User $user): array
    {
        return [
            'user' => $user->load('roles', 'guest'),
        ];
    }

    public function uploadPhoto(User $user, UploadedFile $file): array
    {
        return DB::transaction(function () use ($user, $file) {
            $guest = $user->guest()->first()
                ?? Guest::create([
                    'full_name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ]);

            $previous = $guest->photo_public_id;

            $upload = $this->cloudinary->upload(
                $file,
                'hotel/guests'
            );

            $guest->update([
                'photo_url' => $upload['secure_url'],
                'photo_public_id' => $upload['public_id'],
            ]);

            if ($previous) {
                $this->cloudinary->destroy($previous);
            }

            return [
                'user' => $user->fresh()->load('roles', 'guest'),
            ];
        });
    }

    public function removePhoto(User $user): array
    {
        return DB::transaction(function () use ($user) {
            $guest = $user->guest()->first();

            if ($guest) {
                $publicId = $guest->photo_public_id;

                $guest->update([
                    'photo_url' => null,
                    'photo_public_id' => null,
                ]);

                if ($publicId) {
                    $this->cloudinary->destroy($publicId);
                }
            }

            return [
                'user' => $user->fresh()->load('roles', 'guest'),
            ];
        });
    }
}
