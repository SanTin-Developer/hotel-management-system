<?php

use App\Models\Amenity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::insert([
        ['name' => 'amenities.view', 'guard_name' => 'web'],
        ['name' => 'amenities.create', 'guard_name' => 'web'],
        ['name' => 'amenities.update', 'guard_name' => 'web'],
        ['name' => 'amenities.delete', 'guard_name' => 'web'],
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
        'amenities.view',
        'amenities.create',
        'amenities.update',
        'amenities.delete',
    ]);

    $manager->syncPermissions([
        'amenities.view',
        'amenities.create',
        'amenities.update',
        'amenities.delete',
    ]);

    $staff->syncPermissions([
        'amenities.view',
    ]);
});

function amenityApiUser(string $role): User
{
    $user = User::factory()->create([
        'status' => 'active',
    ]);

    $user->assignRole($role);

    return $user;
}

it('lists amenities publicly', function () {
    Amenity::create([
        'name' => 'Wi-Fi',
        'description' => 'High speed internet',
        'icon' => 'wifi',
    ]);

    Amenity::create([
        'name' => 'Swimming Pool',
        'description' => 'Outdoor pool',
        'icon' => 'pool',
    ]);

    $response = $this->getJson('/api/v1/amenities');

    $response
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'description',
                    'icon',
                    'rooms_count',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);

    expect($response->json('data'))->toHaveCount(2);
});

it('shows one amenity publicly', function () {
    $amenity = Amenity::create([
        'name' => 'Wi-Fi',
        'description' => 'High speed internet',
        'icon' => 'wifi',
    ]);

    $response = $this->getJson(
        "/api/v1/amenities/{$amenity->id}"
    );

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $amenity->id)
        ->assertJsonPath('data.name', 'Wi-Fi')
        ->assertJsonPath('data.icon', 'wifi');
});

it('allows admin to create an amenity', function () {
    $admin = amenityApiUser('admin');

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/amenities', [
            'name' => 'Air Conditioning',
            'description' => 'Central air conditioning',
            'icon' => 'snowflake',
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.name', 'Air Conditioning')
        ->assertJsonPath('data.icon', 'snowflake');

    $this->assertDatabaseHas('amenities', [
        'name' => 'Air Conditioning',
    ]);
});

it('allows manager to update an amenity', function () {
    $manager = amenityApiUser('manager');

    $amenity = Amenity::create([
        'name' => 'Wi-Fi',
        'description' => 'Old description',
        'icon' => 'wifi',
    ]);

    $response = $this
        ->actingAs($manager, 'sanctum')
        ->putJson("/api/v1/amenities/{$amenity->id}", [
            'name' => 'Premium Wi-Fi',
            'description' => 'Updated description',
            'icon' => 'wifi-strong',
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.name', 'Premium Wi-Fi')
        ->assertJsonPath('data.description', 'Updated description')
        ->assertJsonPath('data.icon', 'wifi-strong');

    $this->assertDatabaseHas('amenities', [
        'id' => $amenity->id,
        'name' => 'Premium Wi-Fi',
    ]);
});

it('allows admin to delete an amenity', function () {
    $admin = amenityApiUser('admin');

    $amenity = Amenity::create([
        'name' => 'Temporary Amenity',
        'description' => 'Temporary',
        'icon' => 'test',
    ]);

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/amenities/{$amenity->id}");

    $response
        ->assertOk()
        ->assertJsonPath(
            'message',
            'Amenity deleted successfully.'
        );

    $this->assertDatabaseMissing('amenities', [
        'id' => $amenity->id,
    ]);
});

it('rejects a duplicate amenity name', function () {
    $admin = amenityApiUser('admin');

    Amenity::create([
        'name' => 'Wi-Fi',
        'description' => 'Existing',
        'icon' => 'wifi',
    ]);

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/amenities', [
            'name' => 'Wi-Fi',
            'description' => 'Duplicate',
            'icon' => 'wifi',
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.name.0',
            'An amenity with this name already exists.'
        );
});

it('rejects creating an amenity without a name', function () {
    $admin = amenityApiUser('admin');

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/amenities', [
            'description' => 'Missing name',
            'icon' => 'test',
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.name.0',
            'Amenity name is required.'
        );
});

it('prevents customers from creating amenities', function () {
    $customer = amenityApiUser('customer');

    $response = $this
        ->actingAs($customer, 'sanctum')
        ->postJson('/api/v1/amenities', [
            'name' => 'Customer Amenity',
        ]);

    $response->assertForbidden();
});

it('prevents staff from creating amenities', function () {
    $staff = amenityApiUser('staff');

    $response = $this
        ->actingAs($staff, 'sanctum')
        ->postJson('/api/v1/amenities', [
            'name' => 'Staff Amenity',
        ]);

    $response->assertForbidden();
});

it('prevents customers from updating amenities', function () {
    $customer = amenityApiUser('customer');

    $amenity = Amenity::create([
        'name' => 'Wi-Fi',
        'description' => 'Internet',
        'icon' => 'wifi',
    ]);

    $response = $this
        ->actingAs($customer, 'sanctum')
        ->putJson("/api/v1/amenities/{$amenity->id}", [
            'name' => 'Updated Wi-Fi',
        ]);

    $response->assertForbidden();
});

it('prevents customers from deleting amenities', function () {
    $customer = amenityApiUser('customer');

    $amenity = Amenity::create([
        'name' => 'Wi-Fi',
        'description' => 'Internet',
        'icon' => 'wifi',
    ]);

    $response = $this
        ->actingAs($customer, 'sanctum')
        ->deleteJson("/api/v1/amenities/{$amenity->id}");

    $response->assertForbidden();
});

it('returns 404 for a missing amenity', function () {
    $response = $this->getJson('/api/v1/amenities/999999');

    $response->assertNotFound();
});

it('paginates amenities', function () {
    foreach (range(1, 20) as $number) {
        Amenity::create([
            'name' => "Amenity {$number}",
            'description' => "Description {$number}",
            'icon' => "icon-{$number}",
        ]);
    }

    $response = $this->getJson(
        '/api/v1/amenities?per_page=10&page=1'
    );

    $response
        ->assertOk()
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonPath('meta.total', 20);

    expect($response->json('data'))
        ->toHaveCount(10);
});

it('searches amenities by name', function () {
    Amenity::create([
        'name' => 'Premium Wi-Fi',
        'description' => 'Fast internet',
        'icon' => 'wifi',
    ]);

    Amenity::create([
        'name' => 'Swimming Pool',
        'description' => 'Outdoor pool',
        'icon' => 'pool',
    ]);

    $response = $this->getJson(
        '/api/v1/amenities?search=Premium'
    );

    $response->assertOk();

    expect($response->json('data'))
        ->toHaveCount(1);

    expect(
        $response->json('data.0.name')
    )->toBe('Premium Wi-Fi');
});
