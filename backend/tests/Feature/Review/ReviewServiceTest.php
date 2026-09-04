<?php

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Review;
use App\Services\Review\ReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function reviewService(): ReviewService
{
    return app(ReviewService::class);
}

function reviewServiceGuest(): Guest
{
    return Guest::factory()->create();
}

function reviewServiceBooking(Guest $guest): Booking
{
    return Booking::factory()->create([
        'guest_id' => $guest->id,
    ]);
}

function reviewServiceReview(
    Booking $booking,
    Guest $guest,
    string $status = 'pending'
): Review {
    return Review::create([
        'booking_id' => $booking->id,
        'guest_id' => $guest->id,
        'rating' => 5,
        'comment' => 'Excellent!',
        'status' => $status,
    ]);
}

it('gets all reviews with guest and booking', function () {
    $guest = reviewServiceGuest();
    $booking = reviewServiceBooking($guest);

    reviewServiceReview($booking, $guest);
    reviewServiceReview($booking, $guest);

    $reviews = reviewService()->getAll();

    expect($reviews)->toHaveCount(2);
    expect($reviews->first()->guest)->not->toBeNull();
    expect($reviews->first()->booking)->not->toBeNull();
});

it('filters reviews by status', function () {
    $guest = reviewServiceGuest();
    $booking = reviewServiceBooking($guest);

    reviewServiceReview($booking, $guest, 'pending');
    reviewServiceReview($booking, $guest, 'approved');

    $reviews = reviewService()->getAll(['status' => 'approved']);

    expect($reviews)->toHaveCount(1);
    expect($reviews->first()->status)->toBe('approved');
});

it('filters reviews by rating', function () {
    $guest = reviewServiceGuest();
    $booking = reviewServiceBooking($guest);

    Review::create([
        'booking_id' => $booking->id,
        'guest_id' => $guest->id,
        'rating' => 5,
        'comment' => 'Great',
    ]);

    Review::create([
        'booking_id' => $booking->id,
        'guest_id' => $guest->id,
        'rating' => 2,
        'comment' => 'Poor',
    ]);

    $reviews = reviewService()->getAll(['rating' => 5]);

    expect($reviews)->toHaveCount(1);
    expect($reviews->first()->rating)->toBe(5);
});

it('gets a single review with details', function () {
    $guest = reviewServiceGuest();
    $booking = reviewServiceBooking($guest);

    $review = reviewServiceReview($booking, $guest);

    $result = reviewService()->getById($review);

    expect($result->id)->toBe($review->id);
    expect($result->guest->id)->toBe($guest->id);
    expect($result->booking->id)->toBe($booking->id);
});

it('creates a review', function () {
    $guest = reviewServiceGuest();
    $booking = reviewServiceBooking($guest);

    $review = reviewService()->create([
        'booking_id' => $booking->id,
        'guest_id' => $guest->id,
        'rating' => 5,
        'comment' => 'Wonderful stay',
    ]);

    expect($review)->toBeInstanceOf(Review::class);
    expect($review->rating)->toBe(5);
    expect($review->status)->toBe('pending');

    $this->assertDatabaseHas('reviews', [
        'id' => $review->id,
        'booking_id' => $booking->id,
        'rating' => 5,
    ]);
});

it('updates a review', function () {
    $guest = reviewServiceGuest();
    $booking = reviewServiceBooking($guest);

    $review = reviewServiceReview($booking, $guest);

    $updated = reviewService()->update($review, [
        'rating' => 3,
        'comment' => 'Updated review',
    ]);

    expect($updated->rating)->toBe(3);
    expect($updated->comment)->toBe('Updated review');

    $this->assertDatabaseHas('reviews', [
        'id' => $review->id,
        'rating' => 3,
    ]);
});

it('deletes a review', function () {
    $guest = reviewServiceGuest();
    $booking = reviewServiceBooking($guest);

    $review = reviewServiceReview($booking, $guest);

    reviewService()->delete($review);

    $this->assertDatabaseMissing('reviews', [
        'id' => $review->id,
    ]);
});

it('approves a review', function () {
    $guest = reviewServiceGuest();
    $booking = reviewServiceBooking($guest);

    $review = reviewServiceReview($booking, $guest, 'pending');

    $approved = reviewService()->approve($review);

    expect($approved->status)->toBe('approved');

    $this->assertDatabaseHas('reviews', [
        'id' => $review->id,
        'status' => 'approved',
    ]);
});

it('rejects a review', function () {
    $guest = reviewServiceGuest();
    $booking = reviewServiceBooking($guest);

    $review = reviewServiceReview($booking, $guest, 'pending');

    $rejected = reviewService()->reject($review);

    expect($rejected->status)->toBe('rejected');

    $this->assertDatabaseHas('reviews', [
        'id' => $review->id,
        'status' => 'rejected',
    ]);
});
