<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            [
                'email' => 'admin@hotel.com',
            ],
            [
                'name' => 'Hotel Admin',
                'password' => Hash::make('password'),
            ]
        );

        $admin->assignRole('admin');
    }
}
