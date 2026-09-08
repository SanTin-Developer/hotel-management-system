<?php

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\Booking\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function statusTestGuest(): Guest
{
    return Guest::create([
        'full_name' => 'Status Test Guest',
        'email' => fake()->unique()->safeEmail(),
        'phone' => '012345678',
        'country' => 'Cambodia',
    ]);
}

function statusTestBooking(string $status = 'pending'): Booking
{
    $guest = statusTestGuest();

    return Booking::create([
        'booking_code' => 'STATUS-'.fake()->unique()->numerify('#####'),
        'guest_id' => $guest->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'adults' => 2,
        'children' => 0,
        'total_amount' => 240,
        'booking_source' => 'website',
        'status' => $status,
    ]);
}

it('confirms a pending booking', function () {
    $booking = statusTestBooking();

    $service = app(BookingService::class);

    $updated = $service->confirm($booking);

    expect($updated->status)->toBe('confirmed');

    $this->assertDatabaseHas('booking_status_histories', [
        'booking_id' => $booking->id,
        'status' => 'confirmed',
    ]);
});

it('cancels a pending booking', function () {
    $booking = statusTestBooking();

    app(BookingService::class)->cancel($booking);

    expect($booking->refresh()->status)->toBe('cancelled');

    $this->assertDatabaseHas('booking_status_histories', [
        'booking_id' => $booking->id,
        'status' => 'cancelled',
    ]);
});

it('completes a confirmed booking', function () {
    $booking = statusTestBooking('confirmed');

    app(BookingService::class)->complete($booking);

    expect($booking->refresh()->status)->toBe('completed');

    $this->assertDatabaseHas('booking_status_histories', [
        'booking_id' => $booking->id,
        'status' => 'completed',
    ]);
});

it('checks in a confirmed booking', function () {
    $booking = statusTestBooking('confirmed');

    app(BookingService::class)->checkIn($booking);

    expect($booking->refresh()->status)->toBe('in_house');

    $this->assertDatabaseHas('booking_status_histories', [
        'booking_id' => $booking->id,
        'status' => 'in_house',
    ]);
});

it('completes an in-house booking', function () {
    $booking = statusTestBooking('in_house');

    app(BookingService::class)->complete($booking);

    expect($booking->refresh()->status)->toBe('completed');
});

it('cannot check in a pending booking', function () {
    $booking = statusTestBooking();

    expect(fn () => app(BookingService::class)->checkIn($booking))
        ->toThrow(ValidationException::class);
});

it('cannot complete a pending booking', function () {
    $booking = statusTestBooking();

    expect(fn () => app(BookingService::class)->complete($booking))
        ->toThrow(ValidationException::class);
});

it('cannot confirm a cancelled booking', function () {
    $booking = statusTestBooking('cancelled');

    expect(fn () => app(BookingService::class)->confirm($booking))
        ->toThrow(ValidationException::class);
});

it('cannot change a completed booking', function () {
    $booking = statusTestBooking('completed');

    expect(fn () => app(BookingService::class)->cancel($booking))
        ->toThrow(ValidationException::class);
});

it('records the initial pending history when a booking is created', function () {
    $guest = statusTestGuest();

    $roomType = RoomType::create([
        'name' => 'Status Test Room',
        'capacity' => 2,
        'base_price' => 80,
        'status' => 'active',
    ]);

    $room = Room::create([
        'room_type_id' => $roomType->id,
        'room_number' => 'STATUS-101',
        'floor' => 1,
        'status' => 'available',
    ]);

    $booking = app(BookingService::class)->create([
        'guest_id' => $guest->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'adults' => 2,
        'children' => 0,
        'room_ids' => [$room->id],
    ]);

    $this->assertDatabaseHas('booking_status_histories', [
        'booking_id' => $booking->id,
        'status' => 'pending',
    ]);
});
