<?php

namespace App\Services\Staff;

use App\Models\Staff;
use App\Models\User;
use App\Services\Media\CloudinaryService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StaffService
{
    public function __construct(
        private readonly CloudinaryService $cloudinary
    ) {}
    public function getAll(array $filters = [])
    {
        return Staff::query()
            ->with('user:id,name,email,phone')
            ->when(
                ! empty($filters['search']),
                function ($query) use ($filters) {
                    $search = $filters['search'];

                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('employee_id', 'ILIKE', "%{$search}%")
                            ->orWhere('position', 'ILIKE', "%{$search}%")
                            ->orWhereHas('user', function ($query) use ($search) {
                                $query
                                    ->where('name', 'ILIKE', "%{$search}%")
                                    ->orWhere('email', 'ILIKE', "%{$search}%");
                            });
                    });
                }
            )
            ->when(
                ! empty($filters['status']),
                fn ($query) => $query->where(
                    'status',
                    $filters['status']
                )
            )
            ->when(
                ! empty($filters['position']),
                fn ($query) => $query->where(
                    'position',
                    $filters['position']
                )
            )
            ->orderByDesc('hire_date')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getById(Staff $staff): Staff
    {
        return $staff->load('user:id,name,email,phone,status');
    }

    public function create(array $data): Staff
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'status' => 'active',
            ]);

            if (! empty($data['role'])) {
                $user->assignRole($data['role']);
            } else {
                $user->assignRole('staff');
            }

            return Staff::create([
                'user_id' => $user->id,
                'employee_id' => $data['employee_id'] ?? $this->generateEmployeeId(),
                'position' => $data['position'],
                'hire_date' => $data['hire_date'],
                'status' => $data['status'] ?? 'active',
            ])->load('user:id,name,email,phone');
        });
    }

    public function update(Staff $staff, array $data): Staff
    {
        return DB::transaction(function () use ($staff, $data) {
            $hasUserUpdates = ! empty($data['name'])
                || ! empty($data['email'])
                || ! empty($data['phone'])
                || ! empty($data['password']);

            if ($hasUserUpdates) {
                $userData = array_filter([
                    'name' => $data['name'] ?? null,
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                ], fn ($v) => $v !== null);

                if (! empty($userData)) {
                    $staff->user->update($userData);
                }

                if (! empty($data['password'])) {
                    $staff->user->update([
                        'password' => Hash::make($data['password']),
                    ]);
                }
            }

            $staffData = array_filter([
                'employee_id' => $data['employee_id'] ?? null,
                'position' => $data['position'] ?? null,
                'hire_date' => $data['hire_date'] ?? null,
                'status' => $data['status'] ?? null,
            ], fn ($v) => $v !== null);

            if ($staffData) {
                $staff->update($staffData);
            }

            return $staff->refresh()->load('user:id,name,email,phone');
        });
    }

    public function delete(Staff $staff): void
    {
        DB::transaction(function () use ($staff) {
            $staff->delete();
        });
    }

    public function uploadPhoto(Staff $staff, UploadedFile $file): Staff
    {
        return DB::transaction(function () use ($staff, $file) {
            $previous = $staff->photo_public_id;

            $upload = $this->cloudinary->upload(
                $file,
                'hotel/staff'
            );

            $staff->update([
                'photo_url' => $upload['secure_url'],
                'photo_public_id' => $upload['public_id'],
            ]);

            if ($previous) {
                $this->cloudinary->destroy($previous);
            }

            return $staff->refresh()->load('user:id,name,email,phone');
        });
    }

    public function removePhoto(Staff $staff): Staff
    {
        return DB::transaction(function () use ($staff) {
            $publicId = $staff->photo_public_id;

            $staff->update([
                'photo_url' => null,
                'photo_public_id' => null,
            ]);

            if ($publicId) {
                $this->cloudinary->destroy($publicId);
            }

            return $staff->refresh()->load('user:id,name,email,phone');
        });
    }

    private function generateEmployeeId(): string
    {
        do {
            $id = 'EMP-'.now()->format('Ymd').'-'.Str::upper(Str::random(4));
        } while (Staff::where('employee_id', $id)->exists());

        return $id;
    }
}
