<?php

use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['cache.stores.redis.driver' => 'array']);

    Cache::store('redis')->forget('dashboard:summary');

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $permission = Permission::create([
        'name' => 'dashboard.view',
        'guard_name' => 'web',
    ]);

    $role = Role::create([
        'name' => 'manager',
        'guard_name' => 'web',
    ]);

    $role->givePermissionTo($permission);
});

afterEach(function () {
    Cache::store('redis')->forget('dashboard:summary');

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function fullDashboardUser(): User
{
    $user = User::factory()->create([
        'status' => 'active',
    ]);

    $user->assignRole('manager');

    return $user;
}

function fullDashboardGuest(): Guest
{
    return Guest::create([
        'full_name' => 'Dashboard Test Guest',
        'email' => fake()->unique()->safeEmail(),
        'phone' => '012345678',
        'country' => 'Cambodia',
    ]);
}

function fullDashboardRoomType(): RoomType
{
    return RoomType::create([
        'name' => fake()->unique()->words(2, true),
        'capacity' => 2,
        'base_price' => 100,
        'status' => 'active',
    ]);
}

function fullDashboardRoom(
    RoomType $roomType,
    string $number,
    string $status
): Room {
    return Room::create([
        'room_type_id' => $roomType->id,
        'room_number' => $number,
        'floor' => 1,
        'status' => $status,
        'description' => 'Dashboard test room',
    ]);
}

it('requires authentication', function () {
    $this->getJson('/api/v1/dashboard/summary')
        ->assertUnauthorized();
});

it('returns the complete dashboard summary', function () {
    $user = fullDashboardUser();
    $roomType = fullDashboardRoomType();

    fullDashboardRoom($roomType, '101', 'available');
    fullDashboardRoom($roomType, '102', 'available');
    fullDashboardRoom($roomType, '103', 'occupied');
    fullDashboardRoom($roomType, '104', 'maintenance');
    fullDashboardRoom($roomType, '105', 'cleaning');
    fullDashboardRoom($roomType, '106', 'out_of_service');

    $guest = fullDashboardGuest();

    $booking = Booking::create([
        'booking_code' => 'DASH-FULL-001',
        'guest_id' => $guest->id,
        'check_in' => now()->toDateString(),
        'check_out' => now()->addDays(2)->toDateString(),
        'adults' => 2,
        'children' => 1,
        'total_amount' => 300,
        'booking_source' => 'website',
        'status' => 'confirmed',
    ]);

    Payment::create([
        'booking_id' => $booking->id,
        'amount' => 300,
        'payment_method' => 'cash',
        'transaction_id' => 'DASH-FULL-PAY-001',
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    BookingStatusHistory::create([
        'booking_id' => $booking->id,
        'status' => 'confirmed',
        'changed_by' => $user->id,
        'note' => 'Booking confirmed.',
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/v1/dashboard/summary');

    $response
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'stats' => [
                    'total_rooms',
                    'today_bookings',
                    'total_guests',
                    'today_revenue',
                    'total_revenue',
                    'available_rooms',
                    'occupied_rooms',
                ],

                'revenue_chart' => [
                    'today',
                    'this_week',
                    'this_month',
                    'this_year',
                ],

                'booking_overview' => [
                    'confirmed',
                    'pending',
                    'cancelled',
                    'completed',
                ],

                'recent_bookings' => [
                    '*' => [
                        'id',
                        'booking_code',
                        'guest_id',
                        'check_in',
                        'check_out',
                        'total_amount',
                        'status',
                        'created_at',
                    ],
                ],

                'recent_activities' => [
                    '*' => [
                        'id',
                        'booking_id',
                        'status',
                        'changed_by',
                        'note',
                        'created_at',
                    ],
                ],

                'room_status' => [
                    'available',
                    'occupied',
                    'maintenance',
                    'cleaning',
                    'out_of_service',
                ],
            ],
        ]);

    $response
        ->assertJsonPath('data.stats.total_rooms', 6)
        ->assertJsonPath('data.stats.today_bookings', 1)
        ->assertJsonPath('data.stats.total_guests', 1)
        ->assertJsonPath('data.stats.today_revenue', 300)
        ->assertJsonPath('data.stats.total_revenue', 300)
        ->assertJsonPath('data.stats.available_rooms', 2)
        ->assertJsonPath('data.stats.occupied_rooms', 1);

    $response
        ->assertJsonPath('data.booking_overview.confirmed', 1)
        ->assertJsonPath('data.booking_overview.pending', 0)
        ->assertJsonPath('data.booking_overview.cancelled', 0)
        ->assertJsonPath('data.booking_overview.completed', 0);

    $response
        ->assertJsonPath('data.room_status.available', 2)
        ->assertJsonPath('data.room_status.occupied', 1)
        ->assertJsonPath('data.room_status.maintenance', 1)
        ->assertJsonPath('data.room_status.cleaning', 1)
        ->assertJsonPath('data.room_status.out_of_service', 1);

    expect($response->json('data.recent_bookings'))
        ->toHaveCount(1);

    expect($response->json('data.recent_activities'))
        ->toHaveCount(2);

    expect($response->json('data.revenue_chart.today'))
        ->not->toBeEmpty();

    expect($response->json('data.revenue_chart.this_week'))
        ->not->toBeEmpty();

    expect($response->json('data.revenue_chart.this_month'))
        ->not->toBeEmpty();

    expect($response->json('data.revenue_chart.this_year'))
        ->not->toBeEmpty();
});

it('hides unpaid pending bookings but shows pending with a paid deposit', function () {
    $user = fullDashboardUser();
    $roomType = fullDashboardRoomType();

    fullDashboardRoom($roomType, '101', 'available');

    $guest = fullDashboardGuest();

    $unpaidBooking = Booking::create([
        'booking_code' => 'DASH-PENDING-001',
        'guest_id' => $guest->id,
        'check_in' => now()->toDateString(),
        'check_out' => now()->addDays(2)->toDateString(),
        'adults' => 2,
        'children' => 0,
        'total_amount' => 200,
        'booking_source' => 'website',
        'status' => 'pending',
    ]);

    $paidPendingBooking = Booking::create([
        'booking_code' => 'DASH-PAID-PENDING',
        'guest_id' => $guest->id,
        'check_in' => now()->toDateString(),
        'check_out' => now()->addDays(2)->toDateString(),
        'adults' => 2,
        'children' => 0,
        'total_amount' => 300,
        'deposit_rate' => 20,
        'deposit_amount' => 60,
        'booking_source' => 'website',
        'status' => 'pending',
    ]);

    Payment::create([
        'booking_id' => $paidPendingBooking->id,
        'amount' => 60,
        'payment_method' => 'aba',
        'transaction_id' => 'DASH-DEPOSIT-001',
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $confirmedBooking = Booking::create([
        'booking_code' => 'DASH-CONFIRMED-001',
        'guest_id' => $guest->id,
        'check_in' => now()->toDateString(),
        'check_out' => now()->addDays(2)->toDateString(),
        'adults' => 2,
        'children' => 0,
        'total_amount' => 400,
        'booking_source' => 'website',
        'status' => 'confirmed',
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/v1/dashboard/summary');

    $response
        ->assertOk()
        ->assertJsonPath('data.stats.today_bookings', 2)
        ->assertJsonPath('data.booking_overview.confirmed', 1)
        ->assertJsonPath('data.booking_overview.pending', 1);

    $recentCodes = collect($response->json('data.recent_bookings'))
        ->pluck('booking_code')
        ->all();

    expect($recentCodes)
        ->toHaveCount(2)
        ->toContain($paidPendingBooking->booking_code)
        ->toContain($confirmedBooking->booking_code)
        ->not->toContain($unpaidBooking->booking_code);

    $activityCodes = collect($response->json('data.recent_activities'))
        ->pluck('booking.booking_code')
        ->all();

    expect($activityCodes)
        ->toHaveCount(2)
        ->not->toContain($unpaidBooking->booking_code);
});

it('returns zero values when the database has no dashboard data', function () {
    $user = fullDashboardUser();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/v1/dashboard/summary');

    $response
        ->assertOk()
        ->assertJsonPath('data.stats.total_rooms', 0)
        ->assertJsonPath('data.stats.today_bookings', 0)
        ->assertJsonPath('data.stats.total_guests', 0)
        ->assertJsonPath('data.stats.today_revenue', 0)
        ->assertJsonPath('data.stats.total_revenue', 0)
        ->assertJsonPath('data.stats.available_rooms', 0)
        ->assertJsonPath('data.stats.occupied_rooms', 0)
        ->assertJsonPath('data.booking_overview.confirmed', 0)
        ->assertJsonPath('data.booking_overview.pending', 0)
        ->assertJsonPath('data.booking_overview.cancelled', 0)
        ->assertJsonPath('data.booking_overview.completed', 0)
        ->assertJsonPath('data.room_status.available', 0)
        ->assertJsonPath('data.room_status.occupied', 0)
        ->assertJsonPath('data.room_status.maintenance', 0)
        ->assertJsonPath('data.room_status.cleaning', 0)
        ->assertJsonPath('data.room_status.out_of_service', 0);
});

it('caches the dashboard summary', function () {
    $user = fullDashboardUser();

    $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/v1/dashboard/summary')
        ->assertOk();

    expect(
        Cache::store('redis')->has('dashboard:summary')
    )->toBeTrue();
});
