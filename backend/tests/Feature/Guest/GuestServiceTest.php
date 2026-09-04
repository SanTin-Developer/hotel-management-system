<?php

use App\Models\Booking;
use App\Models\Guest;
use App\Services\Guest\GuestService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function guestService(): GuestService
{
    return app(GuestService::class);
}

function guestServiceGuest(): Guest
{
    return Guest::factory()->create();
}

it('gets all guests with booking count', function () {
    $guest1 = guestServiceGuest();
    $guest2 = guestServiceGuest();

    $guests = guestService()->getAll();

    expect($guests)->toHaveCount(2);
    expect($guests->first()->bookings_count)->toBe(0);
    expect($guests->contains('id', $guest1->id))->toBeTrue();
    expect($guests->contains('id', $guest2->id))->toBeTrue();
});

it('searches guests by name', function () {
    guestServiceGuest();

    Guest::factory()->create([
        'full_name' => 'Unique Search Name',
    ]);

    $guests = guestService()->getAll(['search' => 'Unique Search']);

    expect($guests)->toHaveCount(1);
    expect($guests->first()->full_name)->toBe('Unique Search Name');
});

it('filters guests by country', function () {
    Guest::factory()->create([
        'full_name' => 'Cambodian Guest',
        'country' => 'Cambodia',
    ]);

    guestServiceGuest();

    $guests = guestService()->getAll(['country' => 'Cambodia']);

    expect($guests)->toHaveCount(1);
    expect($guests->first()->country)->toBe('Cambodia');
});

it('gets a single guest with booking and review counts', function () {
    $guest = guestServiceGuest();

    $result = guestService()->getById($guest);

    expect($result->id)->toBe($guest->id);
    expect($result->bookings_count)->toBe(0);
    expect($result->reviews_count)->toBe(0);
});

it('creates a guest', function () {
    $guest = guestService()->create([
        'full_name' => 'Created Guest',
        'email' => 'created@example.com',
        'phone' => '012345678',
    ]);

    expect($guest)->toBeInstanceOf(Guest::class);
    expect($guest->full_name)->toBe('Created Guest');
    expect($guest->email)->toBe('created@example.com');

    $this->assertDatabaseHas('guests', [
        'id' => $guest->id,
        'full_name' => 'Created Guest',
    ]);
});

it('updates a guest', function () {
    $guest = guestServiceGuest();

    $updated = guestService()->update($guest, [
        'full_name' => 'Updated Guest',
        'phone' => '099999999',
    ]);

    expect($updated->full_name)->toBe('Updated Guest');
    expect($updated->phone)->toBe('099999999');

    $this->assertDatabaseHas('guests', [
        'id' => $guest->id,
        'full_name' => 'Updated Guest',
    ]);
});

it('deletes a guest without bookings', function () {
    $guest = guestServiceGuest();

    guestService()->delete($guest);

    $this->assertDatabaseMissing('guests', [
        'id' => $guest->id,
    ]);
});

it('cannot delete a guest with bookings', function () {
    $guest = guestServiceGuest();

    Booking::create([
        'booking_code' => 'SRV-'.fake()->unique()->numerify('#####'),
        'guest_id' => $guest->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'adults' => 2,
        'children' => 0,
        'total_amount' => 240,
        'booking_source' => 'website',
        'status' => 'confirmed',
    ]);

    expect(fn () => guestService()->delete($guest))
        ->toThrow(QueryException::class);

    $this->assertDatabaseHas('guests', [
        'id' => $guest->id,
    ]);
});
