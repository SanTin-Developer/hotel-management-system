# Hotel Management System — Backend API

A production-ready Laravel 13 REST API for hotel operations: room management, bookings, payments, coupons, reviews, guests, staff, and role-based access control.

## Tech Stack

| Component | Technology |
|-----------|-----------|
| Framework | Laravel 13 |
| Language | PHP 8.3+ |
| Database | PostgreSQL |
| Cache / Queue | Redis |
| Auth | Laravel Sanctum (Bearer tokens) |
| RBAC | spatie/laravel-permission |
| Data Layer | spatie/laravel-data, spatie/laravel-query-builder |
| Testing | Pest + PHPUnit |
| Static Analysis | Larastan |
| Code Style | Laravel Pint |

## Quick Start (Docker)

```bash
# From the repository root
docker compose up -d --build

# Run migrations and seed
docker compose exec backend php artisan migrate --seed

# Generate application key (first time)
docker compose exec backend php artisan key:generate
```

The API will be available at `http://localhost:8000`.

## Manual Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## Database Seeding

```bash
php artisan db:seed
```

The seeders create:

- **Roles:** `admin`, `manager`, `staff`, `customer`
- **Permissions:** granular CRUD/moderate permissions per resource
- **Default admin:** `admin@hotel.com` / `password`

## Authentication

All protected routes use Sanctum bearer tokens.

```
POST /api/v1/auth/register      Register (sends OTP email)
POST /api/v1/auth/verify-otp    Verify email with OTP
POST /api/v1/auth/resend-otp    Resend OTP
POST /api/v1/auth/login         Login (returns token)
POST /api/v1/auth/logout        Revoke token
GET  /api/v1/auth/me            Current authenticated user
POST /api/v1/auth/password/forgot
POST /api/v1/auth/password/reset
```

### Login Example

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@hotel.com", "password": "password"}'
```

Response:

```json
{
  "data": {
    "token": "1|abcdef...",
    "user": { "id": 1, "name": "Hotel Admin", "email": "admin@hotel.com" }
  }
}
```

Include the token in subsequent requests:

```
Authorization: Bearer <token>
```

## API Endpoints

All versioned under `/api/v1`.

### Auth
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/auth/register` | — | Register a customer |
| POST | `/auth/verify-otp` | — | Verify email OTP |
| POST | `/auth/resend-otp` | — | Resend verification OTP |
| POST | `/auth/login` | — | Login |
| POST | `/auth/logout` | ✓ | Logout |
| GET | `/auth/me` | ✓ | Current user |
| POST | `/auth/password/forgot` | — | Request password reset |
| POST | `/auth/password/reset` | — | Reset password |

### Room Types
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/room-types` | — | List room types |
| GET | `/room-types/{id}` | — | Show room type |
| POST | `/room-types` | ✓ permission | Create room type |
| PUT | `/room-types/{id}` | ✓ permission | Update room type |
| DELETE | `/room-types/{id}` | ✓ permission | Delete room type |

### Rooms
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/rooms` | — | List rooms |
| GET | `/rooms/{id}` | — | Show room |
| POST | `/rooms` | ✓ permission | Create room |
| PUT | `/rooms/{id}` | ✓ permission | Update room |
| DELETE | `/rooms/{id}` | ✓ permission | Delete room |
| PUT | `/rooms/{id}/amenities` | ✓ permission | Sync amenities |
| DELETE | `/rooms/{id}/amenities/{amenity}` | ✓ permission | Remove amenity |

### Amenities
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/amenities` | — | List amenities |
| GET | `/amenities/{id}` | — | Show amenity |
| POST | `/amenities` | ✓ permission | Create amenity |
| PUT | `/amenities/{id}` | ✓ permission | Update amenity |
| DELETE | `/amenities/{id}` | ✓ permission | Delete amenity |

### Bookings
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/bookings` | ✓ | List bookings |
| GET | `/bookings/availability` | — | Check room availability |
| POST | `/bookings` | ✓ permission | Create booking |
| POST | `/bookings/{id}/confirm` | ✓ permission | Confirm booking |
| POST | `/bookings/{id}/cancel` | ✓ permission | Cancel booking |
| POST | `/bookings/{id}/complete` | ✓ permission | Complete booking |

### Payments
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/payments` | ✓ permission | Create payment |
| POST | `/payments/{id}/paid` | ✓ permission | Mark as paid |
| POST | `/payments/{id}/failed` | ✓ permission | Mark as failed |
| POST | `/payments/{id}/refund` | ✓ permission | Refund payment |

### Guests
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/guests` | ✓ permission | List guests |
| GET | `/guests/{id}` | ✓ permission | Show guest |
| POST | `/guests` | ✓ permission | Create guest |
| PUT | `/guests/{id}` | ✓ permission | Update guest |
| DELETE | `/guests/{id}` | ✓ permission | Delete guest |

### Coupons
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/coupons` | ✓ permission | List coupons |
| GET | `/coupons/{id}` | ✓ permission | Show coupon |
| POST | `/coupons` | ✓ permission | Create coupon |
| PUT | `/coupons/{id}` | ✓ permission | Update coupon |
| DELETE | `/coupons/{id}` | ✓ permission | Delete coupon |

### Reviews
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/reviews` | ✓ | List reviews |
| GET | `/reviews/{id}` | — | Show review |
| POST | `/reviews` | ✓ | Create review |
| PUT | `/reviews/{id}` | ✓ | Update review |
| DELETE | `/reviews/{id}` | ✓ | Delete review |
| POST | `/reviews/{id}/approve` | ✓ permission | Approve review |
| POST | `/reviews/{id}/reject` | ✓ permission | Reject review |

### Staff
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/staff` | ✓ permission | List staff |
| GET | `/staff/{id}` | ✓ permission | Show staff |
| POST | `/staff` | ✓ permission | Create staff |
| PUT | `/staff/{id}` | ✓ permission | Update staff |
| DELETE | `/staff/{id}` | ✓ permission | Delete staff |

### Dashboard
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/dashboard/summary` | ✓ permission | Analytics summary (Redis cached) |

## Roles & Permissions

Four roles with granular permissions:

| Permission | admin | manager | staff | customer |
|------------|:-----:|:-------:|:-----:|:--------:|
| room-types.view | ✓ | ✓ | ✓ | — |
| room-types.create/update/delete | ✓ | ✓ | — | — |
| rooms.view | ✓ | ✓ | ✓ | — |
| rooms.create/update/delete | ✓ | ✓ | — | — |
| amenities.view | ✓ | ✓ | ✓ | — |
| amenities.create/update/delete | ✓ | ✓ | — | — |
| bookings.view | ✓ | ✓ | ✓ | — |
| bookings.create | ✓ | ✓ | — | ✓ |
| bookings.confirm | ✓ | ✓ | — | — |
| bookings.cancel | ✓ | ✓ | — | — |
| bookings.complete | ✓ | ✓ | ✓ | — |
| payments.view | ✓ | ✓ | ✓ | — |
| payments.create/update/refund | ✓ | ✓ | — | — |
| guests.view | ✓ | ✓ | ✓ | — |
| guests.create/update/delete | ✓ | ✓ | — | — |
| coupons.* | ✓ | ✓ | — | — |
| reviews.view | ✓ | ✓ | ✓ | — |
| reviews.create/update/delete | ✓ | ✓ | — | ✓ |
| reviews.approve/reject | ✓ | ✓ | — | — |
| staff.view | ✓ | ✓ | — | — |
| staff.create/update/delete | ✓ | ✓ | — | — |
| dashboard.view | ✓ | ✓ | — | — |

## Business Features

- **Concurrency-safe bookings** using PostgreSQL row locking to prevent double-booking
- **Booking lifecycle** with status history auto-logged via DB triggers
- **Payment balance tracking** — prevents over-payment and validates remaining balance
- **Coupon engine** — percentage/fixed discounts with validity window, min amount, and usage limits
- **Review moderation** workflow (pending → approved/rejected)
- **OTP email verification** during registration
- **Redis-cached dashboard analytics** via PostgreSQL views

## Testing

```bash
# Run the full suite
composer test

# Run with coverage (requires Xdebug/pcov)
php artisan test --coverage

# Run specific test file
php artisan test tests/Feature/Guest/GuestApiTest.php
```

Coverage includes:
- Auth (registration, OTP, login, password reset)
- Bookings (CRUD, status transitions, availability, concurrency)
- Payments (creation, balance checks, invalid states)
- Rooms, Room types, Amenities
- Guests, Coupons, Staff, Reviews
- Dashboard analytics
- Performance/Query audit

## Static Analysis & Code Style

```bash
# Larastan
./vendor/bin/phpstan analyse

# Pint
./vendor/bin/pint

# Pint (check only, for CI)
./vendor/bin/pint --test
```

## Architecture

```
app/
├── Http/
│   ├── Controllers/Api/V1/     # Versioned controllers per resource
│   ├── Requests/Api/V1/        # FormRequest validators per resource
│   └── Resources/Api/V1/       # API resource transformers
├── Policies/                   # Authorization policies
├── Providers/AppServiceProvider.php  # Policy + rate limiter registration
├── Services/                   # Business logic layer per resource
└── Models/                     # Eloquent models
database/
├── migrations/                 # Schema (24 migrations)
├── seeders/                    # Roles, permissions, users
└── factories/                  # Model factories for testing
routes/api.php                  # Versioned API routes
```
