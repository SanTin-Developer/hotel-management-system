<?php

use App\Models\Staff;
use App\Models\User;
use App\Services\Staff\StaffService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'manager', 'guard_name' => 'web']);
    Role::create(['name' => 'staff', 'guard_name' => 'web']);
    Role::create(['name' => 'customer', 'guard_name' => 'web']);
});

function staffService(): StaffService
{
    return app(StaffService::class);
}

it('gets all staff with user details', function () {
    Staff::factory()->create();
    Staff::factory()->create();

    $staff = staffService()->getAll();

    expect($staff)->toHaveCount(2);
    expect($staff->first()->user)->toBeInstanceOf(User::class);
});

it('searches staff by employee id', function () {
    Staff::factory()->create([
        'employee_id' => 'EMP-UNIQUE-01',
    ]);

    Staff::factory()->create();

    $staff = staffService()->getAll(['search' => 'EMP-UNIQUE']);

    expect($staff)->toHaveCount(1);
    expect($staff->first()->employee_id)->toBe('EMP-UNIQUE-01');
});

it('filters staff by status', function () {
    Staff::factory()->active()->create();
    Staff::factory()->inactive()->create();

    $staff = staffService()->getAll(['status' => 'inactive']);

    expect($staff)->toHaveCount(1);
    expect($staff->first()->status)->toBe('inactive');
});

it('gets a single staff with user', function () {
    $staff = Staff::factory()->create();

    $result = staffService()->getById($staff);

    expect($result->id)->toBe($staff->id);
    expect($result->user->id)->toBe($staff->user_id);
});

it('creates a staff member with a user account', function () {
    $staff = staffService()->create([
        'name' => 'Created Staff',
        'email' => 'created-staff@hotel.com',
        'password' => 'password',
        'phone' => '012345678',
        'employee_id' => 'EMP-CREATED',
        'position' => 'Receptionist',
        'hire_date' => now()->toDateString(),
    ]);

    expect($staff)->toBeInstanceOf(Staff::class);
    expect($staff->employee_id)->toBe('EMP-CREATED');
    expect($staff->user->name)->toBe('Created Staff');
    expect($staff->user->email)->toBe('created-staff@hotel.com');
    expect($staff->user->hasRole('staff'))->toBeTrue();

    $this->assertDatabaseHas('users', [
        'email' => 'created-staff@hotel.com',
    ]);

    $this->assertDatabaseHas('staff', [
        'employee_id' => 'EMP-CREATED',
    ]);
});

it('creates a staff member with custom role', function () {
    $staff = staffService()->create([
        'name' => 'Created Manager',
        'email' => 'created-manager@hotel.com',
        'password' => 'password',
        'employee_id' => 'EMP-MGR-1',
        'position' => 'Manager',
        'hire_date' => now()->toDateString(),
        'role' => 'manager',
    ]);

    expect($staff->user->hasRole('manager'))->toBeTrue();
});

it('updates a staff member', function () {
    $staff = Staff::factory()->create();

    $updated = staffService()->update($staff, [
        'position' => 'Manager',
        'status' => 'inactive',
    ]);

    expect($updated->position)->toBe('Manager');
    expect($updated->status)->toBe('inactive');

    $this->assertDatabaseHas('staff', [
        'id' => $staff->id,
        'position' => 'Manager',
        'status' => 'inactive',
    ]);
});

it('updates staff user details', function () {
    $staff = Staff::factory()->create();

    $updated = staffService()->update($staff, [
        'name' => 'Updated Staff Name',
        'phone' => '099999999',
    ]);

    expect($updated->user->name)->toBe('Updated Staff Name');
    expect($updated->user->phone)->toBe('099999999');

    $this->assertDatabaseHas('users', [
        'id' => $staff->user_id,
        'name' => 'Updated Staff Name',
    ]);
});

it('updates staff password', function () {
    $staff = Staff::factory()->create();

    staffService()->update($staff, [
        'password' => 'newpassword',
    ]);

    $this->assertTrue(
        Hash::check('newpassword', $staff->user->fresh()->password)
    );
});

it('deletes a staff member', function () {
    $staff = Staff::factory()->create();

    staffService()->delete($staff);

    $this->assertDatabaseMissing('staff', [
        'id' => $staff->id,
    ]);
});
