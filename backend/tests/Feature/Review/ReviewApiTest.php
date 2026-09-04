<?php

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::insert([
        ['name' => 'reviews.view', 'guard_name' => 'web'],
        ['name' => 'reviews.create', 'guard_name' => 'web'],
        ['name' => 'reviews.update', 'guard_name' => 'web'],
        ['name' => 'reviews.delete', 'guard_name' => 'web'],
        ['name' => 'reviews.approve', 'guard_name' => 'web'],
        ['name' => 'reviews.reject', 'guard_name' => 'web'],
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

    $customer = Role::create([
        'name' => 'customer',
        'guard_name' => 'web',
    ]);

    $admin->syncPermissions([
        'reviews.view',
        'reviews.approve',
        'reviews.reject',
    ]);

    $manager->syncPermissions([
        'reviews.view',
        'reviews.approve',
        'reviews.reject',
    ]);

    $staff->syncPermissions([
        'reviews.view',
    ]);

    $customer->syncPermissions([
        'reviews.create',
        'reviews.update',
        'reviews.delete',
    ]);
});

function reviewApiUser(string $role): User
{
    $user = User::factory()->create([
        'status' => 'active',
        'email' => fake()->unique()->safeEmail(),
    ]);

    $user->assignRole($role);

    return $user;
}

function reviewApiGuest(): Guest
{
    return Guest::factory()->create();
}

function reviewApiBooking(Guest $guest): Booking
{
    return Booking::factory()->create([
        'guest_id' => $guest->id,
    ]);
}

function reviewApiReview(
    Booking $booking,
    Guest $guest,
    string $status = 'pending'
): Review {
    return Review::create([
        'booking_id' => $booking->id,
        'guest_id' => $guest->id,
        'rating' => 5,
        'comment' => 'Great stay!',
        'status' => $status,
    ]);
}

it('requires authentication to list reviews', function () {
    $this->getJson('/api/v1/reviews')->assertUnauthorized();
});

it('lists reviews', function () {
    $user = reviewApiUser('admin');

    $guest = reviewApiGuest();
    $booking = reviewApiBooking($guest);

    reviewApiReview($booking, $guest);
    reviewApiReview($booking, $guest);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/v1/reviews');

    $response
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('filters reviews by status', function () {
    $user = reviewApiUser('admin');

    $guest = reviewApiGuest();
    $booking = reviewApiBooking($guest);

    reviewApiReview($booking, $guest, 'pending');
    reviewApiReview($booking, $guest, 'approved');

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/v1/reviews?status=approved');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'approved');
});

it('shows a review publicly', function () {
    $guest = reviewApiGuest();
    $booking = reviewApiBooking($guest);

    $review = reviewApiReview($booking, $guest);

    $response = $this->getJson("/api/v1/reviews/{$review->id}");

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $review->id)
        ->assertJsonPath('data.rating', 5)
        ->assertJsonPath('data.guest.full_name', $guest->full_name)
        ->assertJsonPath('data.booking.booking_code', $booking->booking_code);
});

it('requires authentication to create a review', function () {
    $guest = reviewApiGuest();
    $booking = reviewApiBooking($guest);

    $this->postJson('/api/v1/reviews', [
        'booking_id' => $booking->id,
        'guest_id' => $guest->id,
        'rating' => 5,
    ])->assertUnauthorized();
});

it('creates a review as pending', function () {
    $user = reviewApiUser('customer');

    $guest = reviewApiGuest();
    $booking = reviewApiBooking($guest);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/reviews', [
            'booking_id' => $booking->id,
            'guest_id' => $guest->id,
            'rating' => 4,
            'comment' => 'Nice hotel',
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.rating', 4)
        ->assertJsonPath('data.comment', 'Nice hotel')
        ->assertJsonPath('data.status', 'pending');

    $this->assertDatabaseHas('reviews', [
        'booking_id' => $booking->id,
        'guest_id' => $guest->id,
        'rating' => 4,
        'status' => 'pending',
    ]);
});

it('validates rating range', function () {
    $user = reviewApiUser('customer');

    $guest = reviewApiGuest();
    $booking = reviewApiBooking($guest);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/reviews', [
            'booking_id' => $booking->id,
            'guest_id' => $guest->id,
            'rating' => 6,
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('errors.rating.0', 'Rating cannot exceed 5.');
});

it('validates booking exists', function () {
    $user = reviewApiUser('customer');

    $guest = reviewApiGuest();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/reviews', [
            'booking_id' => 999999,
            'guest_id' => $guest->id,
            'rating' => 5,
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.booking_id.0',
            'The selected booking does not exist.'
        );
});

it('updates a pending review', function () {
    $user = reviewApiUser('customer');

    $guest = reviewApiGuest();
    $booking = reviewApiBooking($guest);

    $review = reviewApiReview($booking, $guest, 'pending');

    $response = $this
        ->actingAs($user, 'sanctum')
        ->putJson("/api/v1/reviews/{$review->id}", [
            'rating' => 3,
            'comment' => 'Updated comment',
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.rating', 3)
        ->assertJsonPath('data.comment', 'Updated comment');

    $this->assertDatabaseHas('reviews', [
        'id' => $review->id,
        'rating' => 3,
    ]);
});

it('deletes a pending review', function () {
    $user = reviewApiUser('customer');

    $guest = reviewApiGuest();
    $booking = reviewApiBooking($guest);

    $review = reviewApiReview($booking, $guest, 'pending');

    $response = $this
        ->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/reviews/{$review->id}");

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Review deleted successfully.');

    $this->assertDatabaseMissing('reviews', [
        'id' => $review->id,
    ]);
});

it('approves a review', function () {
    $admin = reviewApiUser('admin');

    $guest = reviewApiGuest();
    $booking = reviewApiBooking($guest);

    $review = reviewApiReview($booking, $guest, 'pending');

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->postJson("/api/v1/reviews/{$review->id}/approve");

    $response
        ->assertOk()
        ->assertJsonPath('data.status', 'approved');

    $this->assertDatabaseHas('reviews', [
        'id' => $review->id,
        'status' => 'approved',
    ]);
});

it('rejects a review', function () {
    $admin = reviewApiUser('admin');

    $guest = reviewApiGuest();
    $booking = reviewApiBooking($guest);

    $review = reviewApiReview($booking, $guest, 'pending');

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->postJson("/api/v1/reviews/{$review->id}/reject");

    $response
        ->assertOk()
        ->assertJsonPath('data.status', 'rejected');

    $this->assertDatabaseHas('reviews', [
        'id' => $review->id,
        'status' => 'rejected',
    ]);
});

it('denies customer from listing reviews', function () {
    $customer = reviewApiUser('customer');

    $this
        ->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/reviews')
        ->assertForbidden();
});

it('denies customer from approving reviews', function () {
    $customer = reviewApiUser('customer');

    $guest = reviewApiGuest();
    $booking = reviewApiBooking($guest);

    $review = reviewApiReview($booking, $guest, 'pending');

    $this
        ->actingAs($customer, 'sanctum')
        ->postJson("/api/v1/reviews/{$review->id}/approve")
        ->assertForbidden();
});
