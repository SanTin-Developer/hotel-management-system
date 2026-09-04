<?php

use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::insert([
        ['name' => 'coupons.view', 'guard_name' => 'web'],
        ['name' => 'coupons.create', 'guard_name' => 'web'],
        ['name' => 'coupons.update', 'guard_name' => 'web'],
        ['name' => 'coupons.delete', 'guard_name' => 'web'],
    ]);

    $admin = Role::create([
        'name' => 'admin',
        'guard_name' => 'web',
    ]);

    $manager = Role::create([
        'name' => 'manager',
        'guard_name' => 'web',
    ]);

    $staff = Role::create([
        'name' => 'staff',
        'guard_name' => 'web',
    ]);

    Role::create([
        'name' => 'customer',
        'guard_name' => 'web',
    ]);

    $admin->syncPermissions([
        'coupons.view',
        'coupons.create',
        'coupons.update',
        'coupons.delete',
    ]);

    $manager->syncPermissions([
        'coupons.view',
        'coupons.create',
        'coupons.update',
        'coupons.delete',
    ]);

    $staff->syncPermissions([
        'coupons.view',
    ]);
});

function couponApiUser(string $role): User
{
    $user = User::factory()->create([
        'status' => 'active',
    ]);

    $user->assignRole($role);

    return $user;
}

function couponApiCoupon(): Coupon
{
    return Coupon::factory()->create();
}

it('requires authentication to list coupons', function () {
    $this->getJson('/api/v1/coupons')->assertUnauthorized();
});

it('lists coupons', function () {
    $user = couponApiUser('admin');

    couponApiCoupon();
    couponApiCoupon();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/v1/coupons');

    $response
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('filters coupons by status', function () {
    $user = couponApiUser('admin');

    Coupon::factory()->active()->create([
        'code' => 'ACTIVE-01',
    ]);

    Coupon::factory()->inactive()->create([
        'code' => 'INACTIVE-01',
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/v1/coupons?status=active');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'active');
});

it('filters coupons by discount type', function () {
    $user = couponApiUser('admin');

    Coupon::factory()->percentage()->create();
    Coupon::factory()->fixed()->create();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/v1/coupons?discount_type=percentage');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.discount_type', 'percentage');
});

it('denies customer from listing coupons', function () {
    $customer = couponApiUser('customer');

    $this
        ->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/coupons')
        ->assertForbidden();
});

it('shows a single coupon', function () {
    $user = couponApiUser('admin');

    $coupon = couponApiCoupon();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson("/api/v1/coupons/{$coupon->id}");

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $coupon->id)
        ->assertJsonPath('data.code', $coupon->code)
        ->assertJsonPath('data.status', $coupon->status);
});

it('requires authentication to create a coupon', function () {
    $this->postJson('/api/v1/coupons', [
        'code' => 'TEST-01',
    ])->assertUnauthorized();
});

it('creates a coupon', function () {
    $user = couponApiUser('manager');

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/coupons', [
            'code' => 'SAVE10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_amount' => 50,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(2)->toDateString(),
            'usage_limit' => 100,
            'status' => 'active',
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.code', 'SAVE10')
        ->assertJsonPath('data.discount_type', 'percentage')
        ->assertJsonPath('data.discount_value', '10.00')
        ->assertJsonPath('data.status', 'active');

    $this->assertDatabaseHas('coupons', [
        'code' => 'SAVE10',
        'discount_type' => 'percentage',
    ]);
});

it('rejects duplicate coupon codes', function () {
    $user = couponApiUser('manager');

    Coupon::factory()->create([
        'code' => 'DUP-01',
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/coupons', [
            'code' => 'DUP-01',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('errors.code.0', 'A coupon with this code already exists.');
});

it('validates discount type', function () {
    $user = couponApiUser('manager');

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/coupons', [
            'code' => 'BADTYPE',
            'discount_type' => 'unknown',
            'discount_value' => 10,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.discount_type.0',
            'Discount type must be percentage or fixed.'
        );
});

it('validates end date after start date', function () {
    $user = couponApiUser('manager');

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/coupons', [
            'code' => 'BADDATE',
            'discount_type' => 'fixed',
            'discount_value' => 10,
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => now()->toDateString(),
            'status' => 'active',
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.end_date.0',
            'End date must be after start date.'
        );
});

it('updates a coupon', function () {
    $user = couponApiUser('admin');

    $coupon = couponApiCoupon();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->putJson("/api/v1/coupons/{$coupon->id}", [
            'status' => 'inactive',
            'discount_value' => 25,
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.status', 'inactive')
        ->assertJsonPath('data.discount_value', '25.00');

    $this->assertDatabaseHas('coupons', [
        'id' => $coupon->id,
        'status' => 'inactive',
    ]);
});

it('deletes a coupon', function () {
    $user = couponApiUser('admin');

    $coupon = couponApiCoupon();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/coupons/{$coupon->id}");

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Coupon deleted successfully.');

    $this->assertDatabaseMissing('coupons', [
        'id' => $coupon->id,
    ]);
});

it('deletes a coupon referenced by bookings via null-on-delete', function () {
    $user = couponApiUser('admin');

    $coupon = couponApiCoupon();
    $guest = Guest::factory()->create();

    Booking::create([
        'booking_code' => 'CPN-'.fake()->unique()->numerify('#####'),
        'guest_id' => $guest->id,
        'coupon_id' => $coupon->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'adults' => 2,
        'children' => 0,
        'total_amount' => 240,
        'booking_source' => 'website',
        'status' => 'confirmed',
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/coupons/{$coupon->id}");

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Coupon deleted successfully.');

    $this->assertDatabaseMissing('coupons', [
        'id' => $coupon->id,
    ]);

    $this->assertDatabaseHas('bookings', [
        'coupon_id' => null,
    ]);
});

it('denies coupon creation for staff', function () {
    $staff = couponApiUser('staff');

    $this
        ->actingAs($staff, 'sanctum')
        ->postJson('/api/v1/coupons', [
            'code' => 'NO-STAFF',
            'discount_type' => 'fixed',
            'discount_value' => 10,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ])
        ->assertForbidden();
});
