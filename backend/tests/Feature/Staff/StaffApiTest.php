<?php

use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::insert([
        ['name' => 'staff.view', 'guard_name' => 'web'],
        ['name' => 'staff.create', 'guard_name' => 'web'],
        ['name' => 'staff.update', 'guard_name' => 'web'],
        ['name' => 'staff.delete', 'guard_name' => 'web'],
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
        'staff.view',
        'staff.create',
        'staff.update',
        'staff.delete',
    ]);

    $manager->syncPermissions([
        'staff.view',
        'staff.create',
        'staff.update',
        'staff.delete',
    ]);

    $staff->syncPermissions([
        'staff.view',
    ]);
});

function staffApiUser(string $role): User
{
    $user = User::factory()->create([
        'status' => 'active',
    ]);

    $user->assignRole($role);

    return $user;
}

function staffApiStaff(): Staff
{
    return Staff::factory()->create();
}

it('requires authentication to list staff', function () {
    $this->getJson('/api/v1/staff')->assertUnauthorized();
});

it('lists staff with user details', function () {
    $user = staffApiUser('admin');

    staffApiStaff();
    staffApiStaff();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/v1/staff');

    $response
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('filters staff by position', function () {
    $user = staffApiUser('admin');

    Staff::factory()->create([
        'position' => 'Receptionist',
    ]);

    Staff::factory()->create([
        'position' => 'Housekeeper',
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/v1/staff?position=Receptionist');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.position', 'Receptionist');
});

it('filters staff by status', function () {
    $user = staffApiUser('admin');

    Staff::factory()->active()->create();
    Staff::factory()->inactive()->create();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/v1/staff?status=inactive');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'inactive');
});

it('denies customer from listing staff', function () {
    $customer = staffApiUser('customer');

    $this
        ->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/staff')
        ->assertForbidden();
});

it('shows a single staff member with user', function () {
    $user = staffApiUser('admin');

    $staff = staffApiStaff();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson("/api/v1/staff/{$staff->id}");

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $staff->id)
        ->assertJsonPath('data.employee_id', $staff->employee_id)
        ->assertJsonPath('data.user.name', $staff->user->name);
});

it('requires authentication to create staff', function () {
    $this->postJson('/api/v1/staff', [])->assertUnauthorized();
});

it('creates a staff member with a user account', function () {
    $user = staffApiUser('manager');

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/staff', [
            'name' => 'New Staff',
            'email' => 'staff@hotel.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '012345678',
            'employee_id' => 'EMP-001',
            'position' => 'Receptionist',
            'hire_date' => now()->toDateString(),
            'status' => 'active',
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.employee_id', 'EMP-001')
        ->assertJsonPath('data.position', 'Receptionist')
        ->assertJsonPath('data.user.name', 'New Staff')
        ->assertJsonPath('data.user.email', 'staff@hotel.com');

    $this->assertDatabaseHas('users', [
        'email' => 'staff@hotel.com',
        'name' => 'New Staff',
    ]);

    $this->assertDatabaseHas('staff', [
        'employee_id' => 'EMP-001',
    ]);
});

it('creates a staff member with manager role', function () {
    $user = staffApiUser('admin');

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/staff', [
            'name' => 'New Manager',
            'email' => 'manager2@hotel.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'employee_id' => 'EMP-MGR',
            'position' => 'Manager',
            'hire_date' => now()->toDateString(),
            'role' => 'manager',
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.employee_id', 'EMP-MGR');

    $this->assertDatabaseHas('users', [
        'email' => 'manager2@hotel.com',
    ]);

    $createdUser = User::where('email', 'manager2@hotel.com')->first();
    expect($createdUser->hasRole('manager'))->toBeTrue();
});

it('validates unique email for staff creation', function () {
    $user = staffApiUser('admin');

    User::factory()->create([
        'email' => 'taken@hotel.com',
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/staff', [
            'name' => 'Duplicate Email',
            'email' => 'taken@hotel.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'employee_id' => 'EMP-DUP',
            'position' => 'Receptionist',
            'hire_date' => now()->toDateString(),
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('errors.email.0', 'A user with this email already exists.');
});

it('validates unique employee_id for staff creation', function () {
    $user = staffApiUser('admin');

    staffApiStaff();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/staff', [
            'name' => 'New Staff',
            'email' => 'unique-staff@hotel.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'employee_id' => 'EMP-'.strtoupper(fake()->bothify('#####')),
            'position' => 'Receptionist',
            'hire_date' => now()->toDateString(),
        ]);

    $response->assertCreated();
});

it('validates password confirmation', function () {
    $user = staffApiUser('admin');

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/staff', [
            'name' => 'Bad Password',
            'email' => 'badpass@hotel.com',
            'password' => 'password',
            'password_confirmation' => 'different',
            'employee_id' => 'EMP-BAD',
            'position' => 'Receptionist',
            'hire_date' => now()->toDateString(),
        ]);

    $response->assertStatus(422);
});

it('updates a staff member', function () {
    $user = staffApiUser('admin');

    $staff = staffApiStaff();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->putJson("/api/v1/staff/{$staff->id}", [
            'position' => 'Senior Receptionist',
            'status' => 'inactive',
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.position', 'Senior Receptionist')
        ->assertJsonPath('data.status', 'inactive');

    $this->assertDatabaseHas('staff', [
        'id' => $staff->id,
        'position' => 'Senior Receptionist',
        'status' => 'inactive',
    ]);
});

it('updates staff user details', function () {
    $user = staffApiUser('admin');

    $staff = staffApiStaff();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->putJson("/api/v1/staff/{$staff->id}", [
            'name' => 'Updated Name',
            'phone' => '077777777',
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.user.name', 'Updated Name')
        ->assertJsonPath('data.user.phone', '077777777');
});

it('deletes a staff member', function () {
    $user = staffApiUser('admin');

    $staff = staffApiStaff();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/staff/{$staff->id}");

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Staff member deleted successfully.');

    $this->assertDatabaseMissing('staff', [
        'id' => $staff->id,
    ]);
});

it('denies staff from creating other staff', function () {
    $staffUser = staffApiUser('staff');

    $this
        ->actingAs($staffUser, 'sanctum')
        ->postJson('/api/v1/staff', [
            'name' => 'Should Fail',
            'email' => 'fail@hotel.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'employee_id' => 'EMP-FAIL',
            'position' => 'Receptionist',
            'hire_date' => now()->toDateString(),
        ])
        ->assertForbidden();
});
