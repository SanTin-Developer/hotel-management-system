<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Guest;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProductionDataSeeder extends Seeder
{
    private const DEPOSIT_RATE_CAMBODIA = 20.0;

    private const DEPOSIT_RATE_FOREIGN = 30.0;

    public function run(): void
    {
        $this->wipeTransactionalData();

        $guests = Guest::orderBy('id')->get();
        $rooms = Room::with('roomType')->orderBy('id')->get();
        $coupons = Coupon::where('status', 'active')->get();

        if ($guests->isEmpty() || $rooms->isEmpty()) {
            $this->command?->error(
                'No guests or rooms found. Run SampleDataSeeder first.'
            );

            return;
        }

        $this->ensureCustomerAccounts($guests);

        $managerId = User::whereHas(
            'roles',
            fn ($q) => $q->where('name', '!=', 'customer')
        )->value('id');

        $guestUserId = [];

        foreach ($guests as $guest) {
            $guestUserId[$guest->id] = User::where('email', $guest->email)
                ->value('id');
        }

        DB::transaction(function () use (
            $guests,
            $rooms,
            $coupons,
            $managerId,
            $guestUserId
        ) {
            $this->runWithoutTriggers(function () use (
                $guests,
                $rooms,
                $coupons,
                $managerId,
                $guestUserId
            ) {
                for ($i = 0; $i < 60; $i++) {
                    $this->seedBooking(
                        $guests,
                        $rooms,
                        $coupons,
                        $managerId,
                        $guestUserId,
                        $i
                    );
                }
            });
        });

        $this->command?->info('Production data seeded: 60 bookings created.');
    }

    private function runWithoutTriggers(callable $callback): void
    {
        DB::statement('SET session_replication_role = replica;');

        try {
            $callback();
        } finally {
            DB::statement('SET session_replication_role = DEFAULT;');
        }
    }

    private function wipeTransactionalData(): void
    {
        $this->runWithoutTriggers(function () {
            DB::table('reviews')->truncate();
            DB::table('payments')->truncate();
            DB::table('booking_items')->truncate();
            DB::table('booking_status_histories')->truncate();
            DB::table('bookings')->truncate();
        });

        $this->command?->info('Transactional data wiped.');
    }

    private function ensureCustomerAccounts($guests): void
    {
        foreach ($guests as $guest) {
            if (User::where('email', $guest->email)->exists()) {
                continue;
            }

            User::create([
                'name' => $guest->full_name,
                'email' => $guest->email,
                'password' => Hash::make('password'),
                'phone' => $guest->phone,
                'status' => 'active',
            ])->assignRole('customer');
        }
    }

    private function seedBooking(
        $guests,
        $rooms,
        $coupons,
        ?int $managerId,
        array $guestUserId,
        int $index
    ): void {
        $bookedAt = Carbon::today()->subDays(
            (int) floor($index * (180 / 60))
        )->addHours(random_int(8, 20))
            ->addMinutes(random_int(0, 59));

        $status = $this->pickStatus();

        [$checkIn, $checkOut] = $this->buildStayDates($status, $bookedAt);

        $nights = $checkIn->diffInDays($checkOut);

        $chosenRooms = $rooms->random(random_int(1, 2));

        $baseTotal = 0.0;
        $roomPricing = [];

        foreach ($chosenRooms as $room) {
            $pricePerNight = (float) $room->roomType->base_price;
            $subtotal = round($pricePerNight * $nights, 2);
            $baseTotal += $subtotal;

            $roomPricing[] = [
                'room' => $room,
                'price_per_night' => $pricePerNight,
                'subtotal' => $subtotal,
            ];
        }

        $guest = $guests->random();

        $couponId = null;
        $totalAmount = $baseTotal;
        $discountAmount = 0.0;

        if ($coupons->isNotEmpty() && $index % 4 === 0) {
            $candidate = $coupons->random();

            if ($baseTotal >= (float) $candidate->min_amount) {
                $couponId = $candidate->id;

                $discountAmount = $candidate->discount_type === 'percentage'
                    ? round(
                        $baseTotal * (float) $candidate->discount_value / 100,
                        2
                    )
                    : min((float) $candidate->discount_value, $baseTotal);

                $totalAmount = round($baseTotal - $discountAmount, 2);
            }
        }

        $depositRate = $this->depositRateFor($guest);
        $depositAmount = round($totalAmount * $depositRate / 100, 2);

        $createdBy = $index % 2 === 0
            ? $managerId
            : $guestUserId[$guest->id];

        $bookingId = DB::table('bookings')->insertGetId([
            'booking_code' => $this->bookingCode($bookedAt),
            'guest_id' => $guest->id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'adults' => random_int(1, 4),
            'children' => random_int(0, 2),
            'total_amount' => $totalAmount,
            'deposit_rate' => $depositRate,
            'deposit_amount' => $depositAmount,
            'booking_source' => $this->pickSource($index),
            'created_by' => $createdBy,
            'status' => $status,
            'special_request' => null,
            'coupon_id' => $couponId,
            'created_at' => $bookedAt,
            'updated_at' => $bookedAt,
        ]);

        $this->insertHistory($bookingId, 'pending', $createdBy, $bookedAt);

        foreach ($roomPricing as $pricing) {
            DB::table('booking_items')->insert([
                'booking_id' => $bookingId,
                'room_id' => $pricing['room']->id,
                'price_per_night' => $pricing['price_per_night'],
                'nights' => $nights,
                'subtotal' => $pricing['subtotal'],
                'status' => 'reserved',
                'created_at' => $bookedAt,
                'updated_at' => $bookedAt,
            ]);
        }

        $depositPaid = $status === 'pending'
            ? rand(0, 1) === 1
            : true;

        if ($depositPaid) {
            $paidAt = $bookedAt->copy()->addMinutes(random_int(2, 240));

            DB::table('payments')->insert([
                'booking_id' => $bookingId,
                'amount' => $depositAmount,
                'payment_method' => $this->pickPaymentMethod(),
                'transaction_id' => 'TXN-'.$index.'-'.now()->format('Ymd'),
                'status' => 'paid',
                'paid_at' => $paidAt,
                'created_at' => $paidAt,
                'updated_at' => $paidAt,
            ]);
        }

        switch ($status) {
            case 'completed':
                $this->insertHistory(
                    $bookingId,
                    'confirmed',
                    $createdBy,
                    $bookedAt,
                    1
                );
                $this->insertHistory(
                    $bookingId,
                    'completed',
                    $createdBy,
                    $bookedAt,
                    2
                );
                $this->seedReview($bookingId, $guest);
                break;

            case 'confirmed':
                $this->insertHistory(
                    $bookingId,
                    'confirmed',
                    $createdBy,
                    $bookedAt,
                    1
                );
                break;

            case 'in_house':
                $this->insertHistory(
                    $bookingId,
                    'confirmed',
                    $createdBy,
                    $bookedAt,
                    1
                );
                $this->insertHistory(
                    $bookingId,
                    'in_house',
                    $createdBy,
                    $bookedAt,
                    2
                );
                break;

            case 'cancelled':
                $this->insertHistory(
                    $bookingId,
                    'confirmed',
                    $createdBy,
                    $bookedAt,
                    1
                );
                $this->insertHistory(
                    $bookingId,
                    'cancelled',
                    $createdBy,
                    $bookedAt,
                    2
                );
                $this->refundDepositIfEligible($bookingId, $checkIn);
                break;
        }
    }

    private function insertHistory(
        int $bookingId,
        string $status,
        ?int $changedBy,
        Carbon $bookedAt,
        int $offsetMinutes = 0
    ): void {
        DB::table('booking_status_histories')->insert([
            'booking_id' => $bookingId,
            'status' => $status,
            'changed_by' => $changedBy,
            'note' => ucwords(str_replace('_', ' ', $status)).' by hotel.',
            'created_at' => $bookedAt->copy()->addMinutes($offsetMinutes * 90),
        ]);
    }

    private function refundDepositIfEligible(
        int $bookingId,
        Carbon $checkIn
    ): void {
        $refundCutoff = $checkIn->copy()->startOfDay()->subHours(48);

        if (now()->greaterThanOrEqualTo($refundCutoff)) {
            return;
        }

        DB::table('payments')
            ->where('booking_id', $bookingId)
            ->where('status', 'paid')
            ->update([
                'status' => 'refunded',
                'updated_at' => now(),
            ]);
    }

    private function seedReview(int $bookingId, Guest $guest): void
    {
        if (rand(0, 1) === 0) {
            return;
        }

        $ratings = [5, 5, 4, 4, 4, 3, 5, 4, 5, 3];
        $comments = [
            'Wonderful stay, the room was spotless and staff were very helpful.',
            'Great location and very comfortable bed. Highly recommended.',
            'Excellent service from check-in to check-out.',
            'Beautiful room with a lovely view. Would come back again.',
            'Good value for money. Breakfast could be better though.',
            'Very pleasant experience overall.',
            null,
            'The air conditioning was a bit noisy but otherwise a great stay.',
            'Perfect for a family trip, lots of space.',
            'Decent hotel, clean and quiet.',
            'Amazing hospitality, will definitely return.',
            'Room was nice, the pool was a bonus.',
        ];

        DB::table('reviews')->insert([
            'booking_id' => $bookingId,
            'guest_id' => $guest->id,
            'rating' => $ratings[array_rand($ratings)],
            'comment' => $comments[array_rand($comments)],
            'status' => 'approved',
            'created_at' => now()->subDays(random_int(0, 30)),
            'updated_at' => now()->subDays(random_int(0, 30)),
        ]);
    }

    private function pickStatus(): string
    {
        return $this->weightedPick([
            'completed' => 30,
            'confirmed' => 10,
            'in_house' => 8,
            'pending' => 7,
            'cancelled' => 5,
        ]);
    }

    private function weightedPick(array $distribution): string
    {
        $total = array_sum($distribution);
        $roll = random_int(1, $total);

        foreach ($distribution as $value => $weight) {
            if ($roll <= $weight) {
                return $value;
            }

            $roll -= $weight;
        }

        return 'pending';
    }

    private function buildStayDates(string $status, Carbon $bookedAt): array
    {
        if ($status === 'completed') {
            $checkIn = $bookedAt->copy()->addDays(random_int(2, 10));
            $checkOut = $checkIn->addDays(random_int(1, 5));

            if ($checkOut->isFuture()) {
                $checkOut = Carbon::today()->subDays(random_int(1, 3));
                $checkIn = $checkOut->copy()->subDays(random_int(1, 5));
            }

            return [$checkIn, $checkOut];
        }

        if ($status === 'cancelled') {
            $checkIn = $bookedAt->copy()->addDays(random_int(3, 15));

            return [$checkIn, $checkIn->copy()->addDays(random_int(1, 5))];
        }

        if ($status === 'in_house') {
            $checkIn = Carbon::today();

            return [$checkIn, $checkIn->copy()->addDays(random_int(1, 6))];
        }

        $checkIn = Carbon::today()->addDays(
            $status === 'pending'
                ? random_int(-2, 20)
                : random_int(0, 15)
        );

        return [$checkIn, $checkIn->copy()->addDays(random_int(1, 6))];
    }

    private function bookingCode(Carbon $bookedAt): string
    {
        do {
            $code = 'BK-'.$bookedAt->format('Ymd').'-'.
                strtoupper(Str::random(6));
        } while (Booking::where('booking_code', $code)->exists());

        return $code;
    }

    private function depositRateFor(Guest $guest): float
    {
        $country = strtolower(
            trim((string) ($guest->country ?? $guest->nationality ?? ''))
        );

        return str_contains($country, 'cambodia')
            || str_contains($country, 'khmer')
            ? self::DEPOSIT_RATE_CAMBODIA
            : self::DEPOSIT_RATE_FOREIGN;
    }

    private function pickPaymentMethod(): string
    {
        return ['card', 'aba', 'wing', 'acleda'][random_int(0, 3)];
    }

    private function pickSource(int $index): string
    {
        return ['website', 'website', 'website', 'phone', 'walk_in'][
            $index % 5
        ];
    }
}