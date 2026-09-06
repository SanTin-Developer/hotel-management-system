<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\Coupon;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRoomTypes();
        $this->seedRooms();
        $this->seedAmenities();
        $this->seedGuests();
        $this->seedCoupons();
        $this->seedStaff();
        $this->seedOwnerAdmin();
    }

    private function seedOwnerAdmin(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'santinoeurn0601@gmail.com'],
            [
                'name' => 'SanTin',
                'password' => Hash::make('SanTin0011@@'),
                'status' => 'active',
            ]
        );

        $user->syncRoles('admin');
    }

    private function seedRoomTypes(): void
    {
        $roomTypes = [
            [
                'name' => 'Deluxe Room',
                'description' => 'A comfortable and elegant room with a king-size bed, modern amenities, and a relaxing atmosphere.',
                'capacity' => 2,
                'base_price' => 80.00,
                'size' => 32.00,
                'bed_type' => 'king',
                'image_url' => 'https://res.cloudinary.com/drercy9vt/image/upload/v1788610840/hotel/room-types/c8crsxamwwizzyasxrfb.jpg',
                'image_public_id' => 'hotel/room-types/c8crsxamwwizzyasxrfb',
            ],
            [
                'name' => 'Standard Room',
                'description' => 'A clean and practical room with a double bed, ideal for short comfortable stays.',
                'capacity' => 2,
                'base_price' => 50.00,
                'size' => 22.00,
                'bed_type' => 'double',
                'image_url' => null,
                'image_public_id' => null,
            ],
            [
                'name' => 'Executive Suite',
                'description' => 'A spacious luxury suite designed for guests seeking extra comfort, privacy, and premium facilities.',
                'capacity' => 4,
                'base_price' => 150.00,
                'size' => 55.00,
                'bed_type' => 'king',
                'image_url' => 'https://res.cloudinary.com/drercy9vt/image/upload/v1788610957/hotel/room-types/eysxkddckonqnshuk5ts.jpg',
                'image_public_id' => 'hotel/room-types/eysxkddckonqnshuk5ts',
            ],
            [
                'name' => 'Twin Room',
                'description' => 'A room with two twin beds, perfect for friends or colleagues travelling together.',
                'capacity' => 2,
                'base_price' => 70.00,
                'size' => 25.00,
                'bed_type' => 'twin',
                'image_url' => null,
                'image_public_id' => null,
            ],
            [
                'name' => 'Family Room',
                'description' => 'A spacious family-friendly room with comfortable bedding and enough space for a relaxing stay.',
                'capacity' => 4,
                'base_price' => 120.00,
                'size' => 45.00,
                'bed_type' => 'queen',
                'image_url' => 'https://res.cloudinary.com/drercy9vt/image/upload/v1788611218/hotel/room-types/dqngksfm2jgx85qeadxi.jpg',
                'image_public_id' => 'hotel/room-types/dqngksfm2jgx85qeadxi',
            ],
        ];

        foreach ($roomTypes as $data) {
            $existing = RoomType::where('name', $data['name'])->first();

            $attrs = collect($data)->except('name')->all();

            if ($existing?->image_url) {
                $attrs = collect($attrs)->except(['image_url', 'image_public_id'])->all();
            }

            RoomType::updateOrCreate(
                ['name' => $data['name']],
                $attrs
            );
        }
    }

    private function seedRooms(): void
    {
        $rooms = [
            [
                'room_type' => 'Deluxe Room',
                'room_number' => '101',
                'floor' => 1,
                'status' => 'available',
                'description' => 'A comfortable Deluxe Room with a king-size bed, modern facilities, and a relaxing atmosphere.',
                'image_url' => 'https://res.cloudinary.com/drercy9vt/image/upload/v1788611431/hotel/rooms/drybrnr3w4ywobwvt6we.jpg',
                'image_public_id' => 'hotel/rooms/drybrnr3w4ywobwvt6we',
            ],
            [
                'room_type' => 'Standard Room',
                'room_number' => '102',
                'floor' => 1,
                'status' => 'maintenance',
                'description' => 'A practical Standard Room with a double bed.',
                'image_url' => null,
                'image_public_id' => null,
            ],
            [
                'room_type' => 'Executive Suite',
                'room_number' => '201',
                'floor' => 2,
                'status' => 'available',
                'description' => 'A spacious luxury suite with premium facilities, a comfortable king-size bed, and a beautiful interior.',
                'image_url' => 'https://res.cloudinary.com/drercy9vt/image/upload/v1788611479/hotel/rooms/a0lxzqiktnyfxeazwrs5.jpg',
                'image_public_id' => 'hotel/rooms/a0lxzqiktnyfxeazwrs5',
            ],
            [
                'room_type' => 'Twin Room',
                'room_number' => '301',
                'floor' => 3,
                'status' => 'cleaning',
                'description' => 'A Twin Room with two twin beds.',
                'image_url' => null,
                'image_public_id' => null,
            ],
            [
                'room_type' => 'Family Room',
                'room_number' => '302',
                'floor' => 3,
                'status' => 'available',
                'description' => 'A spacious family room designed for comfortable stays with enough space for families.',
                'image_url' => 'https://res.cloudinary.com/drercy9vt/image/upload/v1788611592/hotel/rooms/fxaazvnhpn00momu7arh.jpg',
                'image_public_id' => 'hotel/rooms/fxaazvnhpn00momu7arh',
            ],
        ];

        foreach ($rooms as $data) {
            $roomType = RoomType::where('name', $data['room_type'])->firstOrFail();

            $existing = Room::where('room_number', $data['room_number'])->first();

            $attrs = collect($data)
                ->except(['room_type', 'room_number'])
                ->put('room_type_id', $roomType->id)
                ->all();

            if ($existing?->image_url) {
                $attrs = collect($attrs)->except(['image_url', 'image_public_id'])->all();
            }

            Room::updateOrCreate(
                ['room_number' => $data['room_number']],
                $attrs
            );
        }
    }

    private function seedAmenities(): void
    {
        $amenities = [
            ['name' => 'Free Wi-Fi', 'description' => 'High-speed wireless internet throughout the room.', 'icon' => 'wifi'],
            ['name' => 'Air Conditioning', 'description' => 'Individually controlled air conditioning and heating.', 'icon' => 'air-vent'],
            ['name' => 'Flat-screen TV', 'description' => '55-inch smart TV with cable channels and streaming apps.', 'icon' => 'tv'],
            ['name' => 'Mini Bar', 'description' => 'Fully stocked mini bar with snacks and drinks.', 'icon' => 'glass-water'],
            ['name' => 'Mini Fridge', 'description' => 'Personal refrigerator for guest use.', 'icon' => 'refrigerator'],
            ['name' => 'In-room Safe', 'description' => 'Electronic safe large enough for a laptop.', 'icon' => 'lock'],
            ['name' => 'Private Bathroom', 'description' => 'En-suite bathroom with rain shower.', 'icon' => 'shower-head'],
            ['name' => 'Hair Dryer', 'description' => 'Hair dryer provided in the bathroom.', 'icon' => 'wind'],
            ['name' => 'Tea & Coffee Maker', 'description' => 'Electric kettle with complimentary tea and coffee.', 'icon' => 'coffee'],
            ['name' => 'Room Service', 'description' => '24-hour in-room dining service.', 'icon' => 'utensils'],
            ['name' => 'Ocean View', 'description' => 'Private balcony overlooking the ocean.', 'icon' => 'mountain'],
            ['name' => 'Work Desk', 'description' => 'Spacious desk with ergonomic chair.', 'icon' => 'notebook-pen'],
            ['name' => 'Pool Access', 'description' => 'Access to the rooftop swimming pool.', 'icon' => 'waves'],
            ['name' => 'Spa & Sauna', 'description' => 'Complimentary access to the spa and sauna area.', 'icon' => 'sparkles'],
        ];

        foreach ($amenities as $data) {
            Amenity::updateOrCreate(
                ['name' => $data['name']],
                collect($data)->except('name')->all()
            );
        }

        $this->attachRoomAmenities();
    }

    private function attachRoomAmenities(): void
    {
        $plans = [
            '101' => ['Free Wi-Fi', 'Air Conditioning', 'Flat-screen TV', 'Mini Bar', 'Private Bathroom', 'Tea & Coffee Maker', 'Work Desk', 'Hair Dryer', 'In-room Safe'],
            '102' => ['Free Wi-Fi', 'Air Conditioning', 'Private Bathroom', 'Hair Dryer'],
            '201' => ['Free Wi-Fi', 'Air Conditioning', 'Flat-screen TV', 'Mini Bar', 'Mini Fridge', 'In-room Safe', 'Private Bathroom', 'Hair Dryer', 'Tea & Coffee Maker', 'Room Service', 'Ocean View', 'Work Desk', 'Pool Access', 'Spa & Sauna'],
            '301' => ['Free Wi-Fi', 'Air Conditioning', 'Flat-screen TV', 'Private Bathroom', 'Hair Dryer'],
            '302' => ['Free Wi-Fi', 'Air Conditioning', 'Flat-screen TV', 'Mini Bar', 'Mini Fridge', 'Private Bathroom', 'Hair Dryer', 'Tea & Coffee Maker', 'Ocean View', 'Pool Access'],
        ];

        foreach ($plans as $roomNumber => $names) {
            $room = Room::where('room_number', $roomNumber)->first();
            if (! $room) {
                continue;
            }

            $room->amenities()->sync(
                Amenity::whereIn('name', $names)->pluck('id')->all()
            );
        }
    }

    private function seedGuests(): void
    {
        $guests = [
            [
                'full_name' => 'Sovannara Chen',
                'email' => 'sovannara.chen@gmail.com',
                'phone' => '016500001',
                'address' => 'Street 271, Phnom Penh, Cambodia',
                'nationality' => 'Cambodian',
                'id_type' => 'passport',
                'id_number' => 'P12345678',
                'gender' => 'male',
                'date_of_birth' => '1990-05-12',
                'country' => 'Cambodia',
            ],
            [
                'full_name' => 'Maria Gonzalez',
                'email' => 'maria.gonzalez@gmail.com',
                'phone' => '017500002',
                'address' => 'Calle Mayor 15, Madrid, Spain',
                'nationality' => 'Spanish',
                'id_type' => 'passport',
                'id_number' => 'P22345678',
                'gender' => 'female',
                'date_of_birth' => '1988-11-02',
                'country' => 'Spain',
            ],
            [
                'full_name' => 'James Okafor',
                'email' => 'james.okafor@gmail.com',
                'phone' => '018500003',
                'address' => 'Victoria Island, Lagos, Nigeria',
                'nationality' => 'Nigerian',
                'id_type' => 'passport',
                'id_number' => 'P32345678',
                'gender' => 'male',
                'date_of_birth' => '1995-02-27',
                'country' => 'Nigeria',
            ],
            [
                'full_name' => 'Yuki Tanaka',
                'email' => 'yuki.tanaka@gmail.com',
                'phone' => '019500004',
                'address' => 'Shibuya, Tokyo, Japan',
                'nationality' => 'Japanese',
                'id_type' => 'passport',
                'id_number' => 'P42345678',
                'gender' => 'female',
                'date_of_birth' => '1992-09-18',
                'country' => 'Japan',
            ],
            [
                'full_name' => 'Elena Petrova',
                'email' => 'elena.petrova@gmail.com',
                'phone' => '011500005',
                'address' => 'Nevsky Prospekt, Saint Petersburg, Russia',
                'nationality' => 'Russian',
                'id_type' => 'national_id',
                'id_number' => 'N52345678',
                'gender' => 'female',
                'date_of_birth' => '1985-07-08',
                'country' => 'Russia',
            ],
            [
                'full_name' => 'Daniel Smith',
                'email' => 'daniel.smith@gmail.com',
                'phone' => '012500006',
                'address' => 'Oxford Street, London, United Kingdom',
                'nationality' => 'British',
                'id_type' => 'passport',
                'id_number' => 'P62345678',
                'gender' => 'male',
                'date_of_birth' => '1979-03-25',
                'country' => 'United Kingdom',
            ],
        ];

        foreach ($guests as $data) {
            Guest::updateOrCreate(
                ['email' => $data['email']],
                collect($data)->except('email')->all()
            );
        }
    }

    private function seedCoupons(): void
    {
        $now = Carbon::now();

        $coupons = [
            ['code' => 'WELCOME10', 'discount_type' => 'percentage', 'discount_value' => 10, 'min_amount' => 50, 'start_date' => $now->copy()->subDays(7), 'end_date' => $now->copy()->addYear(), 'usage_limit' => 100, 'status' => 'active'],
            ['code' => 'SUMMER20', 'discount_type' => 'percentage', 'discount_value' => 20, 'min_amount' => 150, 'start_date' => $now->copy()->addWeek(), 'end_date' => $now->copy()->addMonths(3), 'usage_limit' => 50, 'status' => 'active'],
            ['code' => 'EARLYBIRD15', 'discount_type' => 'percentage', 'discount_value' => 15, 'min_amount' => 100, 'start_date' => $now->copy()->addWeeks(2), 'end_date' => $now->copy()->addMonths(2), 'usage_limit' => 30, 'status' => 'active'],
            ['code' => 'FALL25', 'discount_type' => 'percentage', 'discount_value' => 25, 'min_amount' => 200, 'start_date' => $now->copy()->addMonths(2), 'end_date' => $now->copy()->addMonths(5), 'usage_limit' => 40, 'status' => 'active'],
            ['code' => 'LONGSTAY10', 'discount_type' => 'percentage', 'discount_value' => 10, 'min_amount' => 300, 'start_date' => $now->copy()->subDays(1), 'end_date' => $now->copy()->addMonths(8), 'usage_limit' => 20, 'status' => 'active'],
            ['code' => 'REFERRAL5', 'discount_type' => 'percentage', 'discount_value' => 5, 'min_amount' => 25, 'start_date' => $now->copy()->subDays(30), 'end_date' => $now->copy()->addYear(), 'usage_limit' => null, 'status' => 'inactive'],
            ['code' => 'STAY3X30', 'discount_type' => 'fixed', 'discount_value' => 30, 'min_amount' => 200, 'start_date' => $now->copy()->subDays(3), 'end_date' => $now->copy()->addMonths(6), 'usage_limit' => 25, 'status' => 'active'],
            ['code' => 'WEEKEND50', 'discount_type' => 'fixed', 'discount_value' => 50, 'min_amount' => 300, 'start_date' => $now->copy()->addMonth(), 'end_date' => $now->copy()->addMonths(4), 'usage_limit' => 20, 'status' => 'active'],
            ['code' => 'SUITE100', 'discount_type' => 'fixed', 'discount_value' => 100, 'min_amount' => 500, 'start_date' => $now->copy()->subDays(2), 'end_date' => $now->copy()->addMonths(9), 'usage_limit' => 10, 'status' => 'active'],
            ['code' => 'BIRTHDAY20', 'discount_type' => 'fixed', 'discount_value' => 20, 'min_amount' => 120, 'start_date' => $now->copy()->subDays(5), 'end_date' => $now->copy()->addYear(), 'usage_limit' => null, 'status' => 'active'],
            ['code' => 'FLASHSALE40', 'discount_type' => 'fixed', 'discount_value' => 40, 'min_amount' => 180, 'start_date' => $now->copy()->addDays(5), 'end_date' => $now->copy()->addMonths(1), 'usage_limit' => 15, 'status' => 'active'],
            ['code' => 'HOLIDAY60', 'discount_type' => 'fixed', 'discount_value' => 60, 'min_amount' => 400, 'start_date' => $now->copy()->addMonths(3), 'end_date' => $now->copy()->addMonths(6), 'usage_limit' => 8, 'status' => 'inactive'],
        ];

        foreach ($coupons as $data) {
            Coupon::updateOrCreate(
                ['code' => $data['code']],
                collect($data)->except('code')->all()
            );
        }
    }

    private function seedStaff(): void
    {
        $staffList = [
            [
                'name' => 'Chan Dara',
                'email' => 'dara.chan@hotel.com',
                'password' => 'password123',
                'phone' => '012345671',
                'role' => 'manager',
                'employee_id' => 'EMP-2026-0001',
                'position' => 'Front Desk Manager',
                'hire_date' => '2025-03-15',
            ],
            [
                'name' => 'Sokha Kim',
                'email' => 'sokha.kim@hotel.com',
                'password' => 'password123',
                'phone' => '012345672',
                'role' => 'staff',
                'employee_id' => 'EMP-2026-0002',
                'position' => 'Receptionist',
                'hire_date' => '2025-06-01',
            ],
            [
                'name' => 'Rithy Sreypov',
                'email' => 'sreypov.rithy@hotel.com',
                'password' => 'password123',
                'phone' => '012345673',
                'role' => 'staff',
                'employee_id' => 'EMP-2026-0003',
                'position' => 'Housekeeping Supervisor',
                'hire_date' => '2025-08-20',
            ],
            [
                'name' => 'Vannak Thida',
                'email' => 'thida.vannak@hotel.com',
                'password' => 'password123',
                'phone' => '012345674',
                'role' => 'staff',
                'employee_id' => 'EMP-2026-0004',
                'position' => 'Concierge',
                'hire_date' => '2025-11-05',
            ],
            [
                'name' => 'Pheakdey Dara',
                'email' => 'dara.pheakdey@hotel.com',
                'password' => 'password123',
                'phone' => '012345675',
                'role' => 'staff',
                'employee_id' => 'EMP-2026-0005',
                'position' => 'Maintenance Technician',
                'hire_date' => '2026-01-10',
            ],
        ];

        foreach ($staffList as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($data['password']),
                    'phone' => $data['phone'],
                    'status' => 'active',
                ]
            );

            $user->syncRoles($data['role']);

            Staff::updateOrCreate(
                ['employee_id' => $data['employee_id']],
                [
                    'user_id' => $user->id,
                    'position' => $data['position'],
                    'hire_date' => $data['hire_date'],
                    'status' => 'active',
                ]
            );
        }
    }
}