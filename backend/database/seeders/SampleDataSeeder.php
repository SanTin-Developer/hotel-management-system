<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Coupon;
use App\Models\Guest;
use App\Models\Review;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class SampleDataSeeder extends Seeder
{
    private const IMAGE_BASE = 'https://images.unsplash.com/photo-%s?auto=format&fit=crop&w=1200&q=80';

    private const ROOM_TYPE_SEEDS = [
        [
            'name' => 'Standard Room',
            'name_kh' => 'បន្ទប់ស្តង់ដារ',
            'description' => 'An entry-level luxury room with a plush double bed, elegant furnishings, and refined amenities.',
            'description_kh' => 'បន្ទប់លំដាប់ផ្កាយប្រាំដំបូងគេ មានគ្រែធំដ៏កក់ក្ដៅ គ្រឿងសង្ហារិមទំនើប និងសេវាកម្មដ៏ល្អឥតខ្ចោះ។',
            'capacity' => 2,
            'base_price' => 120.00,
            'size' => 28.00,
            'bed_type' => 'double',
            'images' => [
                '1611892440504-42a792e24d32',
                '1505691938895-1758d7feb511',
                '1582719508461-905c673771fd',
                '1554995207-c18c203602cb',
            ],
        ],
        [
            'name' => 'Deluxe Room',
            'name_kh' => 'បន្ទប់ឌីលុច',
            'description' => 'A high-end room with a king-size bed, premium bathroom, and sophisticated décor.',
            'description_kh' => 'បន្ទប់លំដាប់ខ្ពស់ មានគ្រែស៊ីហ្ស៍ស្ដេច បន្ទប់ទឹកទំនើប និងការតុបតែងបែបឆ្នើម។',
            'capacity' => 2,
            'base_price' => 160.00,
            'size' => 34.00,
            'bed_type' => 'king',
            'images' => [
                '1571896349842-33c89424de2d',
                '1611892440504-42a792e24d32',
                '1566073771259-6a8506099945',
                '1584132967334-10e028bd69f7',
            ],
        ],
        [
            'name' => 'Superior Room',
            'name_kh' => 'បន្ទប់ស៊ុបភីរីយ័រ',
            'description' => 'An upgraded room offering a better view and larger living space than the Deluxe Room.',
            'description_kh' => 'បន្ទប់កម្រិតខ្ពស់ជាងឌីលុច មានទិដ្ឋភាពស្អាត និងទំហំធំទូលាយជាង។',
            'capacity' => 2,
            'base_price' => 200.00,
            'size' => 38.00,
            'bed_type' => 'king',
            'images' => [
                '1590490360182-c33d57733427',
                '1578683010236-d716f9a3f461',
                '1616486338812-3dadae4b4ace',
                '1595576508898-0ad5c879a061',
            ],
        ],
        [
            'name' => 'Executive Room',
            'name_kh' => 'បន្ទប់អាជីវកម្ម',
            'description' => 'A business-traveler focused room with lounge access, a work desk, and priority services.',
            'description_kh' => 'បន្ទប់សម្រាប់អ្នកធ្វើអាជីវកម្ម មានសិទ្ធិចូលបន្ទប់ទទួលភ្ញៀវ តុធ្វើការ និងសេវាកម្មអាទិភាព។',
            'capacity' => 2,
            'base_price' => 240.00,
            'size' => 42.00,
            'bed_type' => 'king',
            'images' => [
                '1631049307264-da0ec9d70304',
                '1560448204-e02f11c3d0e2',
                '1611892440504-42a792e24d32',
                '1549294413-26f195200c16',
            ],
        ],
        [
            'name' => 'Junior Suite',
            'name_kh' => 'ឈុតជូនីយ័រ',
            'description' => 'One large room combining a sleeping area with a separate sitting area for extra comfort.',
            'description_kh' => 'បន្ទប់ធំមួយ ដែលរួមបញ្ចូលកន្លែងគេង និងកន្លែងអង្គុយសម្រាកដាច់ដោយឡែក។',
            'capacity' => 3,
            'base_price' => 320.00,
            'size' => 48.00,
            'bed_type' => 'queen',
            'images' => [
                '1549294413-26f195200c16',
                '1522708323590-d24dbb6b0267',
                '1571896349842-33c89424de2d',
                '1590490360182-c33d57733427',
            ],
        ],
        [
            'name' => 'Suite',
            'name_kh' => 'ឈុត',
            'description' => 'A luxurious suite with a separate bedroom and living room, ideal for longer stays.',
            'description_kh' => 'ឈុតប្រណីត មានបន្ទប់គេង និងបន្ទប់ទទួលភ្ញៀវដាច់ដោយឡែក ស័ក្តិសមសម្រាប់ការស្នាក់នៅយូរ។',
            'capacity' => 3,
            'base_price' => 420.00,
            'size' => 60.00,
            'bed_type' => 'king',
            'images' => [
                '1595576508898-0ad5c879a061',
                '1616486338812-3dadae4b4ace',
                '1566073771259-6a8506099945',
                '1578683010236-d716f9a3f461',
            ],
        ],
        [
            'name' => 'Executive Suite',
            'name_kh' => 'ឈុតអាជីវកម្ម',
            'description' => 'A suite with executive lounge privileges, butler service, and premium in-room amenities.',
            'description_kh' => 'ឈុតដែលមានសិទ្ធិចូលបន្ទប់ទទួលភ្ញៀវសម្រាប់អាជីវកម្ម សេវាកម្មអ្នកបម្រើ និងបរិក្ខារក្នុងបន្ទប់ថ្នាក់ប្រណីត។',
            'capacity' => 3,
            'base_price' => 520.00,
            'size' => 75.00,
            'bed_type' => 'king',
            'images' => [
                '1566073771259-6a8506099945',
                '1631049307264-da0ec9d70304',
                '1584132967334-10e028bd69f7',
                '1611892440504-42a792e24d32',
            ],
        ],
        [
            'name' => 'Presidential Suite',
            'name_kh' => 'ឈុតប្រធានាធិបតី',
            'description' => 'The top-tier suite with multiple rooms, premium amenities, panoramic views, and private service.',
            'description_kh' => 'ឈុតលំដាប់កំពូល មានបន្ទប់ច្រើន បរិក្ខារប្រណីត ទិដ្ឋភាពទូលំទូលាយ និងសេវាកម្មឯកជន។',
            'capacity' => 4,
            'base_price' => 900.00,
            'size' => 120.00,
            'bed_type' => 'king',
            'images' => [
                '1512918728675-ed5a9ecdebfd',
                '1621293950781-5bf5336f2a99',
                '1566073771259-6a8506099945',
                '1631049307264-da0ec9d70304',
            ],
        ],
        [
            'name' => 'Family Room',
            'name_kh' => 'បន្ទប់គ្រួសារ',
            'description' => 'A larger room with comfortable bedding, perfect for families with children.',
            'description_kh' => 'បន្ទប់ធំទូលាយ មានគ្រែស្រួលសម្រាប់គ្រួសារដែលមានកុមារ។',
            'capacity' => 4,
            'base_price' => 300.00,
            'size' => 55.00,
            'bed_type' => 'queen',
            'images' => [
                '1554995207-c18c203602cb',
                '1560448204-e02f11c3d0e2',
                '1582719508461-905c673771fd',
                '1505691938895-1758d7feb511',
            ],
        ],
        [
            'name' => 'Villa',
            'name_kh' => 'វីឡា',
            'description' => 'A standalone unit with a private garden or view, exclusive for the most discerning guests.',
            'description_kh' => 'អគារឯករាជ្យ មានសួនច្បារឯកជន ឬទិដ្ឋភាពស្អាត សម្រាប់ភ្ញៀវដែលចង់បានភាពឯកជន។',
            'capacity' => 4,
            'base_price' => 650.00,
            'size' => 95.00,
            'bed_type' => 'king',
            'images' => [
                '1512918728675-ed5a9ecdebfd',
                '1571896349842-33c89424de2d',
                '1584132967334-10e028bd69f7',
                '1590490360182-c33d57733427',
            ],
        ],
    ];

    private const FLOOR_PLAN = [
        1 => ['Standard Room', 'Standard Room', 'Deluxe Room', 'Deluxe Room', 'Superior Room'],
        2 => ['Standard Room', 'Deluxe Room', 'Superior Room', 'Superior Room', 'Executive Room'],
        3 => ['Deluxe Room', 'Superior Room', 'Executive Room', 'Executive Room', 'Junior Suite'],
        4 => ['Superior Room', 'Executive Room', 'Junior Suite', 'Junior Suite', 'Suite'],
        5 => ['Executive Room', 'Junior Suite', 'Suite', 'Suite', 'Executive Suite'],
        6 => ['Junior Suite', 'Suite', 'Executive Suite', 'Executive Suite', 'Presidential Suite'],
        7 => ['Suite', 'Executive Suite', 'Presidential Suite', 'Presidential Suite', 'Presidential Suite'],
        8 => ['Executive Suite', 'Presidential Suite', 'Family Room', 'Family Room', 'Family Room'],
        9 => ['Family Room', 'Family Room', 'Family Room', 'Villa', 'Villa'],
        10 => ['Villa', 'Villa', 'Villa', 'Presidential Suite', 'Presidential Suite'],
    ];

    private const ROOM_IMAGE_POOL = [
        '1611892440504-42a792e24d32',
        '1571896349842-33c89424de2d',
        '1590490360182-c33d57733427',
        '1566073771259-6a8506099945',
        '1582719508461-905c673771fd',
        '1549294413-26f195200c16',
        '1595576508898-0ad5c879a061',
        '1631049307264-da0ec9d70304',
    ];

    public function run(): void
    {
        // Roles and permissions must exist before users/staff sync their roles
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
        ]);

        $this->seedRoomTypes();
        $this->seedRooms();
        $this->pruneStaleRoomTypes();
        $this->seedAmenities();
        $this->seedGuests();
        $this->seedCoupons();
        $this->seedReviews();
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
        foreach (self::ROOM_TYPE_SEEDS as $data) {
            $images = $data['images'];
            $imageUrls = array_map(fn (string $id) => sprintf(self::IMAGE_BASE, $id), $images);

            $roomType = RoomType::updateOrCreate(
                ['name' => $data['name']],
                collect($data)
                    ->except(['name', 'images'])
                    ->put('image_url', $imageUrls[0] ?? null)
                    ->put('image_public_id', null)
                    ->all()
            );

            $roomType->images()->delete();

            foreach ($imageUrls as $index => $url) {
                $roomType->images()->create([
                    'image_url' => $url,
                    'image_public_id' => null,
                    'sort_order' => $index,
                ]);
            }
        }
    }

    private function seedRooms(): void
    {
        $statusPool = ['available', 'available', 'available', 'occupied', 'cleaning'];

        foreach (self::FLOOR_PLAN as $floor => $typeNames) {
            foreach ($typeNames as $index => $typeName) {
                $roomNumber = (string) (($floor * 100) + ($index + 1));

                $roomType = RoomType::where('name', $typeName)->firstOrFail();

                $imageIds = $this->roomImageIds($floor, $index + 1);
                $imageUrls = array_map(fn (string $id) => sprintf(self::IMAGE_BASE, $id), $imageIds);

                $room = Room::updateOrCreate(
                    ['room_number' => $roomNumber],
                    [
                        'room_type_id' => $roomType->id,
                        'floor' => $floor,
                        'status' => $statusPool[($floor + $index) % count($statusPool)],
                        'description' => "A {$typeName} on floor {$floor} with {$roomType->name_kh} comfort and premium amenities.",
                        'description_kh' => "បន្ទប់{$roomType->name_kh} នៅជាន់ទី {$floor} មានផាសុកភាព និងបរិក្ខារកម្រិតខ្ពស់។",
                        'image_url' => $imageUrls[0] ?? null,
                        'image_public_id' => null,
                    ]
                );

                $room->images()->delete();

                foreach ($imageUrls as $imageIndex => $url) {
                    $room->images()->create([
                        'image_url' => $url,
                        'image_public_id' => null,
                        'sort_order' => $imageIndex,
                    ]);
                }
            }
        }
    }

    private function roomImageIds(int $floor, int $slot): array
    {
        $start = (($floor - 1) * 5 + $slot - 1) % count(self::ROOM_IMAGE_POOL);

        return array_map(
            fn (int $offset) => self::ROOM_IMAGE_POOL[($start + $offset) % count(self::ROOM_IMAGE_POOL)],
            [1, 2, 3, 4]
        );
    }

    private function pruneStaleRoomTypes(): void
    {
        $keepNames = collect(self::ROOM_TYPE_SEEDS)->pluck('name')->all();

        RoomType::whereNotIn('name', $keepNames)->delete();
    }

    private function seedAmenities(): void
    {
        $amenities = [
            ['name' => 'Free Wi-Fi', 'description' => 'High-speed wireless internet throughout the room.', 'name_kh' => 'វ៉ាយហ្វាយឥតគិតថ្លៃ', 'description_kh' => 'អ៊ីនធឺណិតឥតខ្សែល្បឿនលឿនពេញបន្ទប់។', 'icon' => 'wifi'],
            ['name' => 'Air Conditioning', 'description' => 'Individually controlled air conditioning and heating.', 'name_kh' => 'ម៉ាស៊ីនត្រជាក់', 'description_kh' => 'ម៉ាស៊ីនត្រជាក់ និងកំដៅដែលអាចកែសម្រួលបានដោយខ្លួនឯង។', 'icon' => 'air-vent'],
            ['name' => 'Flat-screen TV', 'description' => '55-inch smart TV with cable channels and streaming apps.', 'name_kh' => 'ទូរទស្សន៍អេក្រង់រាបស្មើ', 'description_kh' => 'ទូរទស្សន៍ឆ្លាត ៥៥ អ៊ីញ ភ្ជាប់ជាមួយប៉ុស្តិ៍ខ្សែកាប និងកម្មវិធីស្ទ្រីមីង។', 'icon' => 'tv'],
            ['name' => 'Mini Bar', 'description' => 'Fully stocked mini bar with snacks and drinks.', 'name_kh' => 'បារខ្នាតតូច', 'description_kh' => 'បារខ្នាតតូចដាក់ពេញដោយអាហារសម្រន់ និងភេសជ្ជៈ។', 'icon' => 'glass-water'],
            ['name' => 'Mini Fridge', 'description' => 'Personal refrigerator for guest use.', 'name_kh' => 'ទូរទឹកកកខ្នាតតូច', 'description_kh' => 'ទូរទឹកកកសម្រាប់ភ្ញៀវប្រើប្រាស់។', 'icon' => 'refrigerator'],
            ['name' => 'In-room Safe', 'description' => 'Electronic safe large enough for a laptop.', 'name_kh' => 'សុវត្ថិភាពក្នុងបន្ទប់', 'description_kh' => 'សុវត្ថិភាពអេឡិចត្រូនិក ទំហំគ្រប់គ្រាន់សម្រាប់កុំព្យូទ័រយួរដៃ។', 'icon' => 'lock'],
            ['name' => 'Private Bathroom', 'description' => 'En-suite bathroom with rain shower.', 'name_kh' => 'បន្ទប់ទឹកឯកជន', 'description_kh' => 'បន្ទប់ទឹកក្នុងបន្ទប់ មានផ្កាឈូកភ្លៀង។', 'icon' => 'shower-head'],
            ['name' => 'Hair Dryer', 'description' => 'Hair dryer provided in the bathroom.', 'name_kh' => 'ម៉ាស៊ីនផ្លុំសក់', 'description_kh' => 'ម៉ាស៊ីនផ្លុំសក់ដាក់នៅក្នុងបន្ទប់ទឹក។', 'icon' => 'wind'],
            ['name' => 'Tea & Coffee Maker', 'description' => 'Electric kettle with complimentary tea and coffee.', 'name_kh' => 'កន្លែងធ្វើតែ និងកាហ្វេ', 'description_kh' => 'កំសៀវអគ្គិសនី ជាមួយតែ និងកាហ្វេឥតគិតថ្លៃ។', 'icon' => 'coffee'],
            ['name' => 'Room Service', 'description' => '24-hour in-room dining service.', 'name_kh' => 'សេវាកម្មបន្ទប់', 'description_kh' => 'សេវាកម្មទទួលទានអាហារក្នុងបន្ទប់ ២៤ ម៉ោង។', 'icon' => 'utensils'],
            ['name' => 'Ocean View', 'description' => 'Private balcony overlooking the ocean.', 'name_kh' => 'ទិដ្ឋភាពមហាសមុទ្រ', 'description_kh' => 'រាបស្មើរឯកជនមើលឃើញមហាសមុទ្រ។', 'icon' => 'mountain'],
            ['name' => 'Work Desk', 'description' => 'Spacious desk with ergonomic chair.', 'name_kh' => 'តុធ្វើការ', 'description_kh' => 'តុធំទូលាយ ជាមួយកៅអីអឺហ្គោណូមិក។', 'icon' => 'notebook-pen'],
            ['name' => 'Pool Access', 'description' => 'Access to the rooftop swimming pool.', 'name_kh' => 'សិទ្ធិចូលអាងហែលទឹក', 'description_kh' => 'ការចូលប្រើអាងហែលទឹកលើដំបូល។', 'icon' => 'waves'],
            ['name' => 'Spa & Sauna', 'description' => 'Complimentary access to the spa and sauna area.', 'name_kh' => 'ស្ប៉ា និងសូណា', 'description_kh' => 'ចូលប្រើតំបន់ស្ប៉ា និងសូណាឥតគិតថ្លៃ។', 'icon' => 'sparkles'],
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
        $base = [
            'Free Wi-Fi',
            'Air Conditioning',
            'Flat-screen TV',
            'Private Bathroom',
            'Hair Dryer',
            'Tea & Coffee Maker',
        ];

        $mid = [
            'Mini Bar',
            'Mini Fridge',
            'In-room Safe',
            'Work Desk',
        ];

        $premium = [
            'Room Service',
            'Ocean View',
            'Pool Access',
            'Spa & Sauna',
        ];

        foreach (self::FLOOR_PLAN as $floor => $typeNames) {
            foreach (array_keys($typeNames) as $index) {
                $roomNumber = (string) (($floor * 100) + ($index + 1));

                $room = Room::where('room_number', $roomNumber)->first();
                if (! $room) {
                    continue;
                }

                $names = $base;

                if ($floor >= 3) {
                    $names = array_merge($names, $mid);
                }

                if ($floor >= 6 || $room->roomType->base_price >= 300) {
                    $names = array_merge($names, $premium);
                }

                $room->amenities()->sync(
                    Amenity::whereIn('name', $names)->pluck('id')->all()
                );
            }
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

    private function seedReviews(): void
    {
        $guests = Guest::orderBy('id')->get();
        $rooms = Room::with('roomType')->orderBy('id')->get();

        if ($guests->isEmpty() || $rooms->isEmpty()) {
            return;
        }

        $reviewsSeed = [
            [
                'full_name' => 'Sovannara Chen',
                'rating' => 5,
                'comment' => 'Wonderful stay, the room was spotless and the staff were extremely helpful.',
                'comment_kh' => 'ការស្នាក់នៅដ៏អស្ចារ្យ បន្ទប់ស្អាតស្អំ ហើយបុគ្គលិកមានភាពរួសរាយរាក់ទាក់ខ្លាំងណាស់។',
                'room_type' => 'Executive Suite',
                'days_ago' => 3,
            ],
            [
                'full_name' => 'Maria Gonzalez',
                'rating' => 5,
                'comment' => 'Great location and a very comfortable bed. Highly recommended.',
                'comment_kh' => 'ទីតាំងល្អ និងគ្រែស្រួលណាស់។ ណែនាំឲ្យសាកល្បង។',
                'room_type' => 'Presidential Suite',
                'days_ago' => 6,
            ],
            [
                'full_name' => 'James Okafor',
                'rating' => 4,
                'comment' => 'Excellent service from check-in to check-out. Breakfast could be better.',
                'comment_kh' => 'សេវាកម្មល្អឥតខ្ចោះ ចាប់ពីចូលទទួលបន្ទប់រហូតដល់ចេញ។ ប្រហែលអាហារពេលព្រឹកអាចល្អជាងនេះបន្តិច។',
                'room_type' => 'Deluxe Room',
                'days_ago' => 9,
            ],
            [
                'full_name' => 'Yuki Tanaka',
                'rating' => 5,
                'comment' => 'Beautiful room with a lovely view. Would come back again.',
                'comment_kh' => 'បន្ទប់ស្អាត មានទិដ្ឋភាពស្រស់ស្អាត។ ចង់ត្រឡប់មកស្នាក់នៅទៀត។',
                'room_type' => 'Suite',
                'days_ago' => 12,
            ],
            [
                'full_name' => 'Elena Petrova',
                'rating' => 4,
                'comment' => 'Very pleasant experience overall. The pool was a bonus.',
                'comment_kh' => 'បទពិសោធន៍រីករាយខ្លាំងណាស់។ អាងហែលទឹកជាអត្ថប្រយោជន៍បន្ថែម។',
                'room_type' => 'Villa',
                'days_ago' => 15,
            ],
            [
                'full_name' => 'Daniel Smith',
                'rating' => 5,
                'comment' => 'Perfect for a family trip, lots of space and very clean.',
                'comment_kh' => 'ស័ក្តិសមណាស់សម្រាប់ការធ្វើដំណើរជាគ្រួសារ មានកន្លែងទំនេរច្រើន និងស្អាតណាស់។',
                'room_type' => 'Family Room',
                'days_ago' => 18,
            ],
        ];

        foreach ($reviewsSeed as $index => $data) {
            $guest = $guests->firstWhere('full_name', $data['full_name']);
            $room = $rooms->firstWhere(
                'roomType.name',
                $data['room_type']
            );

            if (! $guest || ! $room) {
                continue;
            }

            $bookingCode = 'SAMPLE-REVIEW-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);

            $booking = Booking::updateOrCreate(
                ['booking_code' => $bookingCode],
                [
                    'guest_id' => $guest->id,
                    'check_in' => Carbon::today()->subDays($data['days_ago'] + 3),
                    'check_out' => Carbon::today()->subDays($data['days_ago']),
                    'adults' => $room->roomType->capacity >= 3 ? 3 : 2,
                    'children' => $data['room_type'] === 'Family Room' ? 2 : 0,
                    'total_amount' => $room->roomType->base_price * 3,
                    'deposit_rate' => 20.00,
                    'deposit_amount' => round($room->roomType->base_price * 3 * 0.20, 2),
                    'booking_source' => 'website',
                    'status' => 'completed',
                ]
            );

            BookingItem::updateOrCreate(
                [
                    'booking_id' => $booking->id,
                    'room_id' => $room->id,
                ],
                [
                    'price_per_night' => $room->roomType->base_price,
                    'nights' => 3,
                    'subtotal' => $room->roomType->base_price * 3,
                    'status' => 'reserved',
                ]
            );

            Review::updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'guest_id' => $guest->id,
                    'rating' => $data['rating'],
                    'comment' => $data['comment'],
                    'comment_kh' => $data['comment_kh'],
                    'status' => 'approved',
                ]
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
