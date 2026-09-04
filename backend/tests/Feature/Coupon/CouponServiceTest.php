<?php

use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Guest;
use App\Services\Coupon\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function couponService(): CouponService
{
    return app(CouponService::class);
}

function couponServiceCoupon(): Coupon
{
    return Coupon::factory()->create();
}

it('gets all coupons with booking count', function () {
    couponServiceCoupon();
    couponServiceCoupon();

    $coupons = couponService()->getAll();

    expect($coupons)->toHaveCount(2);
    expect($coupons->first()->bookings_count)->toBe(0);
});

it('searches coupons by code', function () {
    Coupon::factory()->create([
        'code' => 'SEARCHME-01',
    ]);

    couponServiceCoupon();

    $coupons = couponService()->getAll(['search' => 'SEARCHME']);

    expect($coupons)->toHaveCount(1);
    expect($coupons->first()->code)->toBe('SEARCHME-01');
});

it('filters coupons by status', function () {
    Coupon::factory()->active()->create();
    Coupon::factory()->inactive()->create();

    $coupons = couponService()->getAll(['status' => 'inactive']);

    expect($coupons)->toHaveCount(1);
    expect($coupons->first()->status)->toBe('inactive');
});

it('gets a single coupon', function () {
    $coupon = couponServiceCoupon();

    $result = couponService()->getById($coupon);

    expect($result->id)->toBe($coupon->id);
    expect($result->bookings_count)->toBe(0);
});

it('creates a coupon', function () {
    $coupon = couponService()->create([
        'code' => 'NEWCODE',
        'discount_type' => 'percentage',
        'discount_value' => 15,
        'min_amount' => 100,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonths(2)->toDateString(),
        'usage_limit' => 50,
        'status' => 'active',
    ]);

    expect($coupon)->toBeInstanceOf(Coupon::class);
    expect($coupon->code)->toBe('NEWCODE');
    expect($coupon->discount_value)->toBe('15.00');

    $this->assertDatabaseHas('coupons', [
        'code' => 'NEWCODE',
        'discount_type' => 'percentage',
    ]);
});

it('updates a coupon', function () {
    $coupon = couponServiceCoupon();

    $updated = couponService()->update($coupon, [
        'status' => 'inactive',
        'discount_value' => 30,
    ]);

    expect($updated->status)->toBe('inactive');
    expect($updated->discount_value)->toBe('30.00');

    $this->assertDatabaseHas('coupons', [
        'id' => $coupon->id,
        'status' => 'inactive',
    ]);
});

it('deletes a coupon without bookings', function () {
    $coupon = couponServiceCoupon();

    couponService()->delete($coupon);

    $this->assertDatabaseMissing('coupons', [
        'id' => $coupon->id,
    ]);
});

it('deletes a coupon referencing bookings via null-on-delete', function () {
    $coupon = couponServiceCoupon();
    $guest = Guest::factory()->create();

    Booking::create([
        'booking_code' => 'CPN-SRV-'.fake()->unique()->numerify('#####'),
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

    couponService()->delete($coupon);

    $this->assertDatabaseMissing('coupons', [
        'id' => $coupon->id,
    ]);

    $this->assertDatabaseHas('bookings', [
        'coupon_id' => null,
    ]);
});

it('applies a percentage coupon discount', function () {
    $coupon = Coupon::factory()->percentage()->create([
        'code' => 'PCT-10',
        'discount_value' => 10,
        'min_amount' => 0,
        'status' => 'active',
        'start_date' => now()->subWeek(),
        'end_date' => now()->addMonth(),
    ]);

    $result = couponService()->apply($coupon->id, 200.0);

    expect($result['coupon_id'])->toBe($coupon->id);
    expect($result['code'])->toBe('PCT-10');
    expect($result['discount_type'])->toBe('percentage');
    expect($result['discount_amount'])->toBe(20.0);
    expect($result['final_amount'])->toBe(180.0);
});

it('applies a fixed coupon discount', function () {
    $coupon = Coupon::factory()->fixed()->create([
        'code' => 'FIXED-25',
        'discount_value' => 25,
        'min_amount' => 0,
        'status' => 'active',
        'start_date' => now()->subWeek(),
        'end_date' => now()->addMonth(),
    ]);

    $result = couponService()->apply($coupon->id, 100.0);

    expect((float) $result['discount_amount'])->toBe(25.0);
    expect((float) $result['final_amount'])->toBe(75.0);
});

it('rejects applying an inactive coupon', function () {
    $coupon = Coupon::factory()->inactive()->create();

    expect(fn () => couponService()->apply($coupon->id, 100.0))
        ->toThrow(ValidationException::class, 'This coupon is no longer active.');
});

it('rejects applying a coupon before start date', function () {
    $coupon = Coupon::factory()->create([
        'status' => 'active',
        'start_date' => now()->addWeek(),
        'end_date' => now()->addMonths(2),
    ]);

    expect(fn () => couponService()->apply($coupon->id, 100.0))
        ->toThrow(ValidationException::class, 'This coupon is not yet valid.');
});

it('rejects applying an expired coupon', function () {
    $coupon = Coupon::factory()->create([
        'status' => 'active',
        'start_date' => now()->subMonths(2),
        'end_date' => now()->subWeek(),
    ]);

    expect(fn () => couponService()->apply($coupon->id, 100.0))
        ->toThrow(ValidationException::class, 'This coupon has expired.');
});

it('rejects applying a coupon below minimum amount', function () {
    $coupon = Coupon::factory()->active()->create([
        'min_amount' => 500,
        'start_date' => now()->subWeek(),
        'end_date' => now()->addMonth(),
    ]);

    expect(fn () => couponService()->apply($coupon->id, 100.0))
        ->toThrow(ValidationException::class, 'Minimum order amount of 500.00');
});

it('rejects applying a coupon at usage limit', function () {
    $coupon = Coupon::factory()->active()->create([
        'usage_limit' => 1,
        'start_date' => now()->subWeek(),
        'end_date' => now()->addMonth(),
    ]);

    $guest = Guest::factory()->create();

    Booking::create([
        'booking_code' => 'CPN-USE-'.fake()->unique()->numerify('#####'),
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

    expect(fn () => couponService()->apply($coupon->id, 300.0))
        ->toThrow(ValidationException::class, 'This coupon has reached its usage limit.');
});
