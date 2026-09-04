<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            // Room Types
            'room-types.view',
            'room-types.create',
            'room-types.update',
            'room-types.delete',

            // Rooms
            'rooms.view',
            'rooms.create',
            'rooms.update',
            'rooms.delete',

            // Amenities
            'amenities.view',
            'amenities.create',
            'amenities.update',
            'amenities.delete',

            // Bookings
            'bookings.view',
            'bookings.create',
            'bookings.update',
            'bookings.cancel',
            'bookings.confirm',
            'bookings.complete',

            // Payments
            'payments.view',
            'payments.create',
            'payments.update',
            'payments.refund',

            // Guests
            'guests.view',
            'guests.create',
            'guests.update',
            'guests.delete',

            // Coupons
            'coupons.view',
            'coupons.create',
            'coupons.update',
            'coupons.delete',

            // Reviews
            'reviews.view',
            'reviews.create',
            'reviews.update',
            'reviews.delete',
            'reviews.approve',
            'reviews.reject',

            // Staff
            'staff.view',
            'staff.create',
            'staff.update',
            'staff.delete',

            // Dashboard
            'dashboard.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $manager = Role::firstOrCreate([
            'name' => 'manager',
            'guard_name' => 'web',
        ]);

        $staff = Role::firstOrCreate([
            'name' => 'staff',
            'guard_name' => 'web',
        ]);

        $customer = Role::firstOrCreate([
            'name' => 'customer',
            'guard_name' => 'web',
        ]);

        $admin->syncPermissions($permissions);

        $manager->syncPermissions($permissions);

        $staff->syncPermissions([
            'room-types.view',
            'rooms.view',
            'amenities.view',
            'bookings.view',
            'payments.view',
            'guests.view',
            'reviews.view',
        ]);

        $customer->syncPermissions([
            'bookings.create',
            'reviews.create',
            'reviews.update',
            'reviews.delete',
        ]);
    }
}
