# 02 · Requirements

## 2.1 Stakeholders

| Stakeholder | Interest | Representative requirements |
| --- | --- | --- |
| **Hotel owner / Admin** | Control the whole system | Full permission set, staff management, dashboard |
| **Hotel management / Managers** | Run daily operations | Booking lifecycle, payments, coupons, guests, reviews, reports |
| **Front-desk staff** | Handle reservations and check-ins | Booking view/actions, room status, guest lookup, payment recording |
| **Guests / Customers** | Book and manage stays online | Search, book, pay deposit, view/cancel bookings, review, bilingual UI |
| **Visitors (public)** | Browse the hotel | Room catalogue, amenities, offers, contact form, approved reviews |
| **Developers / operators** | Build, run, and monitor | Clean API, Docker deployment, CI, backups, seeded demo data |

## 2.2 Assumptions and Constraints

- The system manages a **single hotel property** ("Kumpuchea Otel", Phnom Penh). Multi-property/channel-manager support is out of scope.
- Prices are stored and displayed in **USD**.
- The customer website is bilingual **English / Khmer**; the admin console is English-only.
- The backend requires **PHP 8.3+, PostgreSQL, Redis**, and expects to run under Docker Compose or a compatible environment.
- No real payment gateway is integrated; "payments" are **records** of cash/bank/online transactions, including Khmer market methods (ABA, Wing, ACLEDA).
- E-mail is configurable via Laravel mail settings; the shipped `.env.example` uses `MAIL_MAILER=log` (used for development).

## 2.3 Functional Requirements

Status legend: **✓ Implemented** · **◐ Partial** · **✗ Planned** · **∘ Disabled in code**

### FR-A — Authentication & Account Management

| ID | Requirement | Priority | Status |
| --- | --- | --- | --- |
| FR-A01 | Guest can register with name, email/phone, password; account is activated via a 6-digit OTP e-mailed to the address | High | ✓ |
| FR-A02 | User can log in with **email or phone** plus password | High | ✓ |
| FR-A03 | User can log out; the active Sanctum token is revoked | High | ✓ |
| FR-A04 | User can fetch their own profile (`GET /auth/me`) | High | ✓ |
| FR-A05 | User can update their own profile details (incl. Khmer fields where available) | Medium | ✓ |
| FR-A06 | User can upload or remove a profile photo (hosted on Cloudinary) | Medium | ✓ |
| FR-A07 | User can request a password-reset code by e-mail; OTP expires after 10 minutes and limits attempts | High | ✓ |
| FR-A08 | User can reset their password with the OTP | High | ✓ |
| FR-A09 | Auth endpoints are rate-limited (`login` and `otp` limiters) | High | ✓ |

### FR-B — Room Catalog

| ID | Requirement | Priority | Status |
| --- | --- | --- | --- |
| FR-B01 | Staff manage room types: name, description (+Khmer), capacity, base price, size, bed type, status, image | High | ✓ |
| FR-B02 | Staff manage rooms: room type, room number, floor, status, description | High | ✓ |
| FR-B03 | Staff manage amenities: name/description (EN + KH) and icon | Medium | ✓ |
| FR-B04 | Staff attach amenities to a room and upload a room image gallery | High | ✓ |
| FR-B05 | Staff change a room's status (available, occupied, maintenance, cleaning, out_of_service) | High | ✓ |
| FR-B06 | Public visitors can list room types/rooms and open a room detail page via slug | High | ✓ |

### FR-C — Availability & Pricing

| ID | Requirement | Priority | Status |
| --- | --- | --- | --- |
| FR-C01 | Availability is checked for a date range; rooms with overlapping pending/confirmed bookings or non-available status are excluded | High | ✓ |
| FR-C02 | An availability **calendar** endpoint returns per-day availability | Medium | ✓ |
| FR-C03 | Night count = checkout − checkin; base total = Σ room base price × nights | High | ✓ |
| FR-C04 | Coupons (percentage/fixed) adjust the total before deposit calculation | High | ✓ |
| FR-C05 | Deposit amount is derived from a deposit rate: **20 % for guests from Cambodia/Khmer, 30 % otherwise** (server-side rule). The customer site estimates with a flat 20 % and shows the authoritative rate from the booking after creation | High | ✓ ◐ (client estimate uses 20 %) |

### FR-D — Bookings

| ID | Requirement | Priority | Status |
| --- | --- | --- | --- |
| FR-D01 | A booking covers one or more rooms, a guest, dates, guest counts, optional coupon and special request | High | ✓ |
| FR-D02 | Booking source is tracked: website, phone, walk-in, third-party | Medium | ✓ |
| FR-D03 | Guests see their own bookings (guest filtered), sorted & paginated | High | ✓ |
| FR-D04 | Staff search/filter bookings by text and status, with pagination | High | ✓ |
| FR-D05 | Staff confirm a pending booking; check-in a confirmed booking; complete an in-house booking; cancel a booking | High | ✓ |
| FR-D06 | Guest can request cancellation of a booking (`cancellation_requested`) | High | ✓ |
| FR-D07 | Staff approve or reject a cancellation request; approving returns the booking to `cancelled` | High | ✓ |
| FR-D08 | Refundability: a cancellation is refundable when requested earlier than **48 hours before check-in** | High | ✓ |
| FR-D09 | A daily scheduled job deletes cancelled bookings and their items after a retention window | Medium | ✓ |
| FR-D10 | Guests/staff receive notification e-mails for booking events (queued) | Medium | ✓ (mailer default `log`) |
| FR-D11 | Staff can edit an existing booking    | Low | **✗** not implemented (no route / no `BookingController::update()`; only an unused `UpdateBookingRequest`) |
| FR-D12 | Guest can pay the deposit online and download a PDF invoice | High | ✓ (recorded, not gateway-processed) |

### FR-E — Payments

| ID | Requirement | Priority | Status |
| --- | --- | --- | --- |
| FR-E01 | Staff record a payment against a booking (amount, method, transaction ID auto-generated when blank) | High | ✓ |
| FR-E02 | A recorded payment cannot exceed the booking's remaining balance | High | ✓ |
| FR-E03 | Staff mark a payment `paid`, `failed`, or `refunded` (refund records a transaction ID) | High | ✓ |
| FR-E04 | Payment methods include cash, card, bank transfer, online — and the Khmer methods ABA / Wing / ACLEDA / cash-on-arrival are additionally accepted in validation and UIs | High | ◐ (methods beyond the enum are not gateway-processed) |
| FR-E05 | Standalone `POST /v1/payments` records a payment (used by the admin console); an unused `CreatePaymentRequest` stub also exists | Medium | ✓ (enabled via `StorePaymentRequest`) |

### FR-F — Coupons

| ID | Requirement | Priority | Status |
| --- | --- | --- | --- |
| FR-F01 | Admin manages coupons: code, type (percentage/fixed), value, min amount, validity dates, usage limit, status | High | ✓ |
| FR-F02 | Public endpoint lists **active** coupons | Medium | ✓ |
| FR-F03 | Coupon codes are validated against an amount (validity, usage, min amount) | High | ✓ |
| FR-F04 | A coupon is applied to the booking total (percentage vs flat) | High | ✓ |

### FR-G — Guests

| ID | Requirement | Priority | Status |
| --- | --- | --- | --- |
| FR-G01 | Staff manage guest records: name, contact, address, nationality, country, ID type & number, gender, DOB | High | ✓ |
| FR-G02 | A Guest profile is **auto-created on login** when the user has none | High | ✓ |
| FR-G03 | Guest records are searchable and appear in the booking screen picker | Medium | ✓ |

### FR-H — Reviews

| ID | Requirement | Priority | Status |
| --- | --- | --- | --- |
| FR-H01 | Authenticated guest submits a review (rating 1–5 + optional comment) | High | ✓ |
| FR-H02 | Authenticated user can update/delete a review (routed) | Medium | ✓ (self-authorization intended) |
| FR-H03 | Staff can approve or reject reviews; only **approved** reviews are public | High | ✓ |
| FR-H04 | Public `approved` feed with rating/comment list | High | ✓ |
| FR-H05 | Customer site shows sample fallback reviews when the database has none | Low | ◐ (deliberate demo fallback) |

### FR-I — Staff & Access Control

| ID | Requirement | Priority | Status |
| --- | --- | --- | --- |
| FR-I01 | Admin/manager creates staff accounts with name, email, phone, role, position, employee ID, hire date, photo | High | ✓ |
| FR-I02 | Roles (admin, manager, staff) map to permission sets; permission middleware protects every write route | High | ✓ |
| FR-I03 | Admin console hides/shows navigation and routes by role | High | ✓ |

### FR-J — Dashboard & Reporting

| ID | Requirement | Priority | Status |
| --- | --- | --- | --- |
| FR-J01 | `GET /dashboard/summary` returns period metrics (bookings, revenue, occupancy, arrivals/departures, status counts) | High | ✓ |
| FR-J02 | Dashboard supports periods: today, this week, this month, this year | Medium | ✓ |
| FR-J03 | Dashboard auto-refreshes (admin console polls every 15 s) | Medium | ✓ |

### FR-K — Export

| ID | Requirement | Priority | Status |
| --- | --- | --- | --- |
| FR-K01 | CSV export of bookings (optional status/search) | Medium | ◐ (API ✓, no admin UI) |
| FR-K02 | CSV export of guests (optional search) | Medium | ◐ (API ✓, no admin UI) |
| FR-K03 | CSV export of payments (optional status) | Medium | ◐ (API ✓, no admin UI) |

### FR-L — Customer Website (UX)

| ID | Requirement | Priority | Status |
| --- | --- | --- | --- |
| FR-L01 | Language switcher English/Khmer, persisted | High | ✓ |
| FR-L02 | Home page: hero, floating search widget, room types, why-book, amenities, offers, reviews, contact | High | ✓ |
| FR-L03 | Rooms page driven by URL query (check-in/out, guests) with debounced search; room detail by slug | High | ✓ |
| FR-L04 | Offers page lists active coupons with discount labels | Medium | ✓ |
| FR-L05 | Booking flow: dates → rooms/guests → coupon → guest info (login/register surfaced inside flow) → review → create | High | ✓ |
| FR-L06 | Payment page: deposit amount, offline method instructions + QR codes, triggers deposit payment API | High | ✓ (recorded) |
| FR-L07 | Confirmation page renders a printable PDF invoice (download) | High | ✓ |
| FR-L08 | Profile: overview, my bookings (+filters), booking details (+cancel request), settings incl. photo | High | ✓ |
| FR-L09 | Contact page sends messages via EmailJS from the browser | Medium | ✓ |
| FR-L10 | About / policy pages, reviews section, amenities, footer with hotel info | Medium | ✓ |
| FR-L11 | Responsive layout with mobile menu and floating scroll-to-top | Medium | ✓ |

### FR-M — Admin Console (UX)

| ID | Requirement | Priority | Status |
| --- | --- | --- | --- |
| FR-M01 | Role-aware sidebar and protected routes (login required; staff-only for admin/manager areas) | High | ✓ |
| FR-M02 | CRUD screens built on DataTable + search + pagination + confirm dialogs + detail modal/sheet | High | ✓ |
| FR-M03 | Booking detail drawer shows guest, stay, rooms, payments, totals | High | ✓ |
| FR-M04 | Global navbar search across bookings, guests, rooms, coupons | Medium | ✓ |
| FR-M05 | Dashboard page with metric cards and a revenue/occupancy bar chart | High | ✓ |
| FR-M06 | Profile page with role badge and logout | Medium | ✓ |
| FR-M07 | Login + forgot/reset password screens | High | ✓ |

## 2.4 Role–Permission Matrix

Permissions are granted exactly as defined in `backend/database/seeders/PermissionSeeder.php`. Write-protected routes in `routes/api.php` use these permission middleware names.

Legend: **✓** granted · **◐** partial (see notes) · **—** not granted

| Permission | admin | manager | staff | customer |
| --- | --- | --- | --- | --- |
| `room-types.view` | ✓ | ✓ | ✓ | — |
| `room-types.create` / `.update` / `.delete` | ✓ | ✓ | — | — |
| `rooms.view` | ✓ | ✓ | ✓ | — |
| `rooms.create` / `.update` / `.delete` | ✓ | ✓ | — | — |
| `amenities.view` | ✓ | ✓ | ✓ | — |
| `amenities.create` / `.update` / `.delete` | ✓ | ✓ | — | — |
| `bookings.view` | ✓ | ✓ | ✓ | — |
| `bookings.create` | ✓ | ✓ | — | ✓ |
| `bookings.cancel` / `.confirm` / `.checkin` / `.complete` | ✓ | ✓ | — | — |
| `bookings.update` (defined but unused by routes) | ✓ | ✓ | — | — |
| `payments.view` | ✓ | ✓ | ✓ | — |
| `payments.create` / `.update` / `.refund` | ✓ | ✓ | — | — |
| `guests.view` | ✓ | ✓ | ✓ | — |
| `guests.create` / `.update` / `.delete` | ✓ | ✓ | — | — |
| `coupons.view` / `.create` / `.update` / `.delete` | ✓ | ✓ | — | — |
| `reviews.view` | ✓ | ✓ | ✓ | — |
| `reviews.create` / `.update` / `.delete` | ✓ | ✓ | — | ✓ |
| `reviews.approve` / `.reject` | ✓ | ✓ | — | — |
| `staff.view` / `.create` / `.update` / `.delete` | ✓ | ✓ | — | — |
| `dashboard.view` | ✓ | ✓ | — | — |

> Notes:
> - `bookings.update` is seeded but **no route uses it**; the corresponding request's `authorize()` returns `false` anyway (FR-D11).
> - `reviews.create/.update/.delete` are granted to customers, and those review routes require only `auth:sanctum`; staff moderate via `.approve`/`.reject`.
> - Viewing **approved** reviews on the public site is *not* permission-gated (public endpoint).
> - The admin console additionally hides the Staff screen from `staff`-role users (`RoleRoute roles={["admin","manager"]}`).

## 2.5 Non-Functional Requirements

| Category | Requirement | Status |
| --- | --- | --- |
| **Security** | All write operations require a valid Sanctum bearer token; sensitive modules additionally require a permission | ✓ |
| **Security** | Brute-force protection via Redis-backed rate limiters: `login` (5/min), `otp` (5/min), `availability`, `booking-create` (10/min), `payment-create` | ✓ |
| **Security** | No secrets hard-coded in the repository; API keys live in `.env` | ✓ |
| **Security** | CORS restricted to configured `FRONTEND_URL` | ✓ |
| **Data integrity** | Availability excludes overlapping pending/confirmed bookings and non-`available` rooms | ✓ |
| **Auditability** | Booking status changes append rows to `booking_status_histories` (DB trigger) | ✓ |
| **Reliability** | Queues handle notification e-mails; worker restarts on failure (Docker `unless-stopped`) | ✓ |
| **Reliability** | Daily automated DB backups (`pg_dump` custom) with a 14-day retention; manual PowerShell backup/restore scripts | ✓ |
| **Performance** | Availability endpoints throttled; admin console polls the dashboard (15 s interval) rather than websockets | ✓ |
| **Portability** | Entire stack runs via `docker compose up` (see §10) | ✓ |
| **Testability** | Backend test suite runs automatically in GitHub Actions CI | ✓ |
| **Code quality** | Laravel Pint formatting; Larastan static analysis at level configured in `phpstan.neon` | ✓ |
| **Usability** | Bilingual customer UI, responsive layouts, debounced search, clear empty/loading/error states | ✓ |
| **Compatibility** | Modern evergreen browsers; React 19 + Vite builds | ✓ |

## 2.6 Acceptance Criteria Summary

The system is considered acceptable when a reviewer can:

1. Run the full stack with `docker compose up --build`, seed `SAMPLE` demo data, and log in as `admin@hotel.com` (role: admin) — see §10/§11.
2. Walk the customer journey end to end: browse rooms → check availability → book → pay deposit (recorded) → receive booking + PDF invoice from the "My bookings" area.
3. Walk the staff journey: create a walk-in booking, confirm → check-in → complete it; record and settle a payment; approve/reject a cancellation request; moderate a review; create a coupon; view the dashboard.
4. Verify role restrictions (e.g., a `staff`-role account cannot open the Staff screen or create room types).
5. Verify the documented **partial implementations** (§1.9) behave exactly as described — e.g., payment methods beyond the enum are storable, `in_house` works despite being absent from the enum, and booking editing is unavailable (no endpoint exists).

> Known test-suite caveat: two backend feature tests (auth validation messages) currently fail; they are tracked and unrelated to the acceptance criteria above