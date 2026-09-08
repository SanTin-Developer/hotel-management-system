# 06 · Features Specification

This section documents all major functional capabilities of the Hotel Management System. Each feature specification details its operational purpose, actors, preconditions, inputs, workflows, business rules, outputs, error handling, API endpoints, and associated database entities.

---

## 6.1 Authentication & Account Management

### Feature 1: Customer Registration & Photo Upload
- **Purpose**: Enable new guests to register for an online booking account with personal identity details, contact information, and optional avatar image.
- **User / Actors**: Public Visitors / Prospective Guests.
- **Preconditions**: Email address and phone number must not already belong to an existing active account.
- **Input**:
  - `full_name` (string, max 150)
  - `email` (string, valid email format)
  - `phone` (string, max 30)
  - `password` (string, min 8 characters)
  - `password_confirmation` (string, matching password)
  - `country` (string, required)
  - `id_type` (optional string: `passport`, `national_id`, `driving_license`)
  - `id_number` (optional string, max 100)
  - `photo` (optional image file: jpeg, png, webp, max 5MB)
- **Process**:
  1. System validates inputs and uploads the photo to Cloudinary (`hotel/guests` folder) if supplied.
  2. Generates a secure 6-digit OTP (`random_int(100000, 999999)`).
  3. Hashes the password and OTP using Bcrypt.
  4. Creates or updates a staging record in `registration_otps` with an expiration timestamp set 10 minutes in the future.
  5. Queues `RegistrationOtpMail` to deliver the code to the user's email address.
- **Business Rules**:
  - Raw OTP codes are never stored in the database.
  - Registration requests are throttled to 5 per minute per IP.
- **Output**: HTTP 201 with `verification_id`, email masked confirmation, and expiration countdown.
- **Error Handling**: Returns HTTP 422 if email/phone is registered or validation fails; HTTP 429 if rate limit is exceeded.
- **Related API Endpoints**: `POST /api/v1/auth/register`
- **Related Database Tables**: `registration_otps`, `users`

---

### Feature 2: OTP Verification & Account Activation
- **Purpose**: Verify guest ownership of the registered email address and atomically provision active user and guest profiles.
- **User / Actors**: Prospective Guests.
- **Preconditions**: Valid `verification_id` exists in `registration_otps`; OTP has not expired; attempts < 5.
- **Input**: `verification_id` (integer), `otp` (6-digit numeric string).
- **Process**:
  1. System locks the `registration_otps` row for update.
  2. Compares submitted OTP against `otp_hash` using `Hash::check()`.
  3. Increments `attempts` counter if incorrect.
  4. On successful match, within an atomic database transaction:
     - Creates active user in `users` table.
     - Assigns the `customer` role via Spatie Permission.
     - Provisions corresponding `guests` profile with country, ID type/number, and Cloudinary photo.
     - Generates a Laravel Sanctum personal access token (`customer_auth`).
     - Deletes the temporary `registration_otps` staging record.
- **Business Rules**:
  - Maximum 5 incorrect attempts before the staging record is permanently invalidated.
  - Expired OTPs (> 10 minutes) cannot be verified.
- **Output**: HTTP 200 with authenticated `user` object (including roles and guest profile) and plain-text Sanctum bearer token.
- **Error Handling**: HTTP 422 with `"The provided OTP is incorrect."` or `"The OTP has expired. Please request a new OTP."`
- **Related API Endpoints**: `POST /api/v1/auth/verify-otp`
- **Related Database Tables**: `registration_otps`, `users`, `guests`, `model_has_roles`, `personal_access_tokens`

---

### Feature 3: OTP Resend
- **Purpose**: Allow users to request a fresh 6-digit verification code if the previous email was delayed or expired.
- **User / Actors**: Prospective Guests.
- **Preconditions**: Unverified `verification_id` exists; rate limit window has elapsed.
- **Input**: `verification_id` (integer).
- **Process**: Generates a new 6-digit code, updates `otp_hash` and `expires_at` (+10 mins), resets `attempts` to 0, and dispatches `RegistrationOtpMail`.
- **Business Rules**: Throttled to 1 request every 60 seconds per email key.
- **Output**: HTTP 200 with confirmation message and renewed `verification_id`.
- **Error Handling**: HTTP 422 if ID not found or already verified; HTTP 429 if throttled.
- **Related API Endpoints**: `POST /api/v1/auth/resend-otp`
- **Related Database Tables**: `registration_otps`

---

### Feature 4: User Login (Email or Phone)
- **Purpose**: Authenticate existing customers and hotel staff into their respective web portals.
- **User / Actors**: Customers, Front-Desk Staff, Managers, Administrators.
- **Preconditions**: User account must exist with `status = 'active'`.
- **Input**: `email` (string identifier: email address or phone number), `password` (string).
- **Process**:
  1. System normalizes identifier (lowercased, trimmed).
  2. Queries `users` table where `email = identifier OR phone = identifier`.
  3. Verifies password hash using `Hash::check()`.
  4. Checks `user.status === 'active'`.
  5. If the user lacks an associated `guests` profile, auto-provisions one to ensure customer portal compatibility.
  6. Issues a new Sanctum token (`customer-auth`).
- **Business Rules**:
  - Rate limited to 5 failed attempts per minute (`throttle:login`).
  - Supports dual identification (email or phone).
- **Output**: HTTP 200 with `user` payload (roles, permissions, guest profile) and bearer `token`.
- **Error Handling**:
  - HTTP 404: `"No account found with this email or phone. Please register first."`
  - HTTP 401: `"Incorrect password. Please try again."`
  - HTTP 403: `"Your account is inactive. Please contact support."`
- **Related API Endpoints**: `POST /api/v1/auth/login`
- **Related Database Tables**: `users`, `guests`, `personal_access_tokens`

---

### Feature 5: Forgot & Reset Password
- **Purpose**: Provide a secure, self-service password recovery flow using email OTP codes.
- **User / Actors**: Any registered user.
- **Preconditions**: Account exists with a registered email address.
- **Input**:
  - *Forgot Step*: `email` (string).
  - *Reset Step*: `email`, `otp` (6-digit string), `password`, `password_confirmation`.
- **Process**:
  1. *Forgot*: System generates a 6-digit OTP, stores hashed token in Redis under key `password_reset:v2:{email}` with a 10-minute TTL, and emails the code.
  2. *Reset*: System retrieves Redis cache entry, checks expiration and max attempts (5), compares hash via `Hash::check()`, updates `users.password`, and deletes the Redis cache key.
- **Business Rules**:
  - Rate limited to 3 requests per 10 minutes (`password-reset:{email}`).
  - Generic success message returned even if email does not exist to prevent user enumeration.
- **Output**: HTTP 200 with `"Password has been reset successfully."`
- **Error Handling**: HTTP 422 for invalid/expired OTP or password confirmation mismatch.
- **Related API Endpoints**: `POST /api/v1/auth/password/forgot`, `POST /api/v1/auth/password/reset`
- **Related Database Tables / Cache**: Redis cache store (`password_reset:v2:*`), `users`

---

### Feature 6: User Profile & Photo Management
- **Purpose**: Allow authenticated users to view/update profile data and upload or delete their Cloudinary avatar.
- **User / Actors**: Authenticated Users (`customer`, `staff`, `manager`, `admin`).
- **Preconditions**: Valid Sanctum Bearer token in `Authorization` header.
- **Input**:
  - *Update*: `full_name`, `phone`, `address`, `nationality`, `gender`, `date_of_birth`, `country`, `id_type`, `id_number`.
  - *Upload Photo*: `photo` (file, image, max 5MB).
- **Process**: Updates `users` table and synchronizes corresponding attributes in `guests` or `staff`. Cloudinary service handles asset upload and cleanup of old public IDs.
- **Output**: HTTP 200 with updated user resource.
- **Related API Endpoints**: `GET /api/v1/auth/me`, `PUT /api/v1/auth/me`, `POST /api/v1/auth/me/photo`, `DELETE /api/v1/auth/me/photo`
- **Related Database Tables**: `users`, `guests`, `staff`

---

## 6.2 Room & Catalog Management

### Feature 7: Room Type Management
- **Purpose**: Configure room categories, baseline pricing, guest capacities, and room dimensions.
- **User / Actors**: Managers, Administrators.
- **Preconditions**: Authenticated user holds `room-types.*` permissions.
- **Input**: `name` (unique string), `description`, `capacity` (int 1-10), `base_price` (numeric > 0), `size` (sqm), `bed_type`, `status` (`active`, `inactive`), `image` (file).
- **Process**: CRUD operations executed against `room_types` table. Uploading images updates `image_url` and `image_public_id` via Cloudinary.
- **Business Rules**: Deleting a room type that is referenced by rooms with active bookings is prevented by database foreign key constraints.
- **Output**: HTTP 200 / 201 with `RoomTypeResource`.
- **Related API Endpoints**: `GET /api/v1/room-types`, `POST /api/v1/room-types`, `GET /api/v1/room-types/{id}`, `PUT /api/v1/room-types/{id}`, `DELETE /api/v1/room-types/{id}`, `POST /api/v1/room-types/{id}/image`
- **Related Database Tables**: `room_types`

---

### Feature 8: Room Inventory & Gallery Management
- **Purpose**: Manage physical room records, door numbers, floor assignments, attached amenities, and multi-image photo galleries.
- **User / Actors**: Staff (read-only), Managers, Administrators.
- **Preconditions**: Valid permission (`rooms.view`, `rooms.create`, `rooms.update`, `rooms.delete`).
- **Input**: `room_type_id`, `room_number` (unique string), `floor` (integer), `status`, `description`, `amenity_ids` (array), `image` (file).
- **Process**:
  1. Room records stored in `rooms`.
  2. Amenities synchronized via `room_amenities` pivot table.
  3. Image uploads insert records into `room_images` gallery table with automatic `sort_order` incrementation.
- **Business Rules**: Room deletion is rejected with HTTP 409 if the room is referenced in historical or active `booking_items`.
- **Output**: HTTP 200 / 201 with populated `RoomResource` (including nested room type, amenities, and image gallery).
- **Related API Endpoints**: `GET /api/v1/rooms`, `POST /api/v1/rooms`, `GET /api/v1/rooms/{id}`, `PUT /api/v1/rooms/{id}`, `DELETE /api/v1/rooms/{id}`, `PUT /api/v1/rooms/{id}/amenities`, `POST /api/v1/rooms/{id}/image`, `DELETE /api/v1/rooms/{id}/images/{image}`
- **Related Database Tables**: `rooms`, `room_types`, `room_amenities`, `room_images`

---

### Feature 9: Room Status State Machine
- **Purpose**: Enable housekeeping and front-desk personnel to transition room physical condition (e.g. to Cleaning after check-out, or Maintenance).
- **User / Actors**: Managers, Administrators (with `rooms.update` permission).
- **Input**: `status` (`available`, `occupied`, `maintenance`, `cleaning`, `out_of_service`), optional `note`.
- **Process**: Updates `rooms.status`. Database trigger `trg_rooms_status_history` intercepts the update and writes an immutable audit record to `room_status_histories`.
- **Output**: HTTP 200 with refreshed `RoomResource`.
- **Related API Endpoints**: `PUT /api/v1/rooms/{id}/status`
- **Related Database Tables**: `rooms`, `room_status_histories`

---

### Feature 10: Amenity Management with Bilingual Support
- **Purpose**: Maintain hotel and room perks with dual English and Khmer titles and descriptions.
- **User / Actors**: Managers, Administrators.
- **Input**: `name` (EN), `name_kh` (Khmer), `description` (EN), `description_kh` (Khmer), `icon` (Lucide icon identifier).
- **Process**: Stores amenities in `amenities` table; customer site switches between EN and KH columns according to current locale.
- **Output**: HTTP 200 / 201 with `AmenityResource`.
- **Related API Endpoints**: `GET /api/v1/amenities`, `POST /api/v1/amenities`, `PUT /api/v1/amenities/{id}`, `DELETE /api/v1/amenities/{id}`
- **Related Database Tables**: `amenities`, `room_amenities`

---

## 6.3 Booking & Availability Engine

### Feature 11: Real-Time Availability & Calendar Query
- **Purpose**: Compute available rooms and daily availability matrices for any requested date window without race conditions.
- **User / Actors**: Public Visitors, Customers, Staff.
- **Preconditions**: `check_in` is today or future; `check_out` is after `check_in`.
- **Input**: Query parameters `check_in=YYYY-MM-DD`, `check_out=YYYY-MM-DD`.
- **Process**:
  1. Identifies rooms where `status = 'available'`.
  2. Excludes rooms having overlapping bookings where `status IN ('pending', 'confirmed')` and:
     $$\text{booking.check\_in} < \text{req.check\_out} \quad \text{AND} \quad \text{booking.check\_out} > \text{req.check\_in}$$
  3. `availabilityCalendar` endpoint iterates day-by-day generating an availability boolean and date map per room.
- **Business Rules**: Both endpoints are rate-limited via Redis (`throttle:availability`).
- **Output**: JSON collection of available rooms with room types and amenities.
- **Related API Endpoints**: `GET /api/v1/bookings/availability`, `GET /api/v1/bookings/calendar`
- **Related Database Tables**: `rooms`, `bookings`, `booking_items`

---

### Feature 12: Booking Creation (Online & Front-Desk)
- **Purpose**: Create a binding reservation for one or more rooms with price calculations, coupon discounts, and deposit determination.
- **User / Actors**: Customers (`customer` role), Managers, Administrators.
- **Preconditions**: Selected rooms must be available; customer must book under their own guest profile (unless manager/admin).
- **Input**:
  - `guest_id` (integer)
  - `check_in`, `check_out` (dates)
  - `adults` (1-20), `children` (0-20)
  - `room_ids` (array of distinct room integers)
  - `coupon_id` (optional integer)
  - `special_request` (optional text, max 2000 chars)
  - `booking_source` (`website`, `phone`, `walk_in`, `third_party`)
- **Process**:
  1. Transaction initiated; rooms locked with `FOR UPDATE`.
  2. Verifies room existence, availability, and absence of overlaps.
  3. Computes stay nights: $\text{nights} = \text{check\_out} - \text{check\_in}$.
  4. Computes base total: $\sum (\text{base\_price} \times \text{nights})$.
  5. Evaluates coupon rules (percentage vs fixed, minimum spend).
  6. Determines deposit rate:
     - **20.0%** if guest nationality or country contains `"cambodia"` or `"khmer"`.
     - **30.0%** for all other guests.
  7. Inserts record into `bookings` with code `BK-YYYYMMDD-XXXXXX` and status `pending`.
  8. Inserts `booking_items` line items and `booking_status_histories` entry.
- **Business Rules**: Non-admin customers attempting to create a booking for an unowned guest ID receive HTTP 422.
- **Output**: HTTP 201 with populated `BookingResource`.
- **Related API Endpoints**: `POST /api/v1/bookings`
- **Related Database Tables**: `bookings`, `booking_items`, `booking_status_histories`, `coupons`, `guests`, `rooms`

---

### Feature 13: Booking Lifecycle State Machine
- **Purpose**: Manage reservation progress through authorized operational states:
  `pending` → `confirmed` → `in_house` → `completed`.
- **User / Actors**: Front-Desk Staff, Managers, Administrators.
- **Workflow State Transitions**:
  ```mermaid
  stateDiagram-v2
      [*] --> Pending: Booking Created
      Pending --> Confirmed: Staff Confirms / Deposit Settled
      Pending --> Cancelled: Cancelled by Staff
      Pending --> CancellationRequested: Guest Requests (<48h check-in)

      Confirmed --> InHouse: Staff Executes Check-In
      Confirmed --> Cancelled: Cancelled by Staff
      Confirmed --> CancellationRequested: Guest Requests (<48h check-in)

      InHouse --> Completed: Staff Executes Check-Out / Complete
      InHouse --> Cancelled: Abnormal Staff Cancellation

      CancellationRequested --> Cancelled: Staff Approves Cancellation
      CancellationRequested --> Confirmed: Staff Rejects Cancellation (Restored)
      CancellationRequested --> Pending: Staff Rejects Cancellation (Restored)

      Completed --> [*]
      Cancelled --> [*]
  ```
- **Business Rules**:
  - Transitions outside the allowed state map throw HTTP 422 validation exceptions.
  - State changes dispatch queued notification emails (`SendBookingStatusEmail`, `SendBookingConfirmationEmail`).
- **Related API Endpoints**:
  - `POST /api/v1/bookings/{id}/confirm`
  - `POST /api/v1/bookings/{id}/check-in`
  - `POST /api/v1/bookings/{id}/complete`
  - `POST /api/v1/bookings/{id}/cancel`
- **Related Database Tables**: `bookings`, `booking_status_histories`

---

### Feature 14: Cancellation Request & Refund Evaluation
- **Purpose**: Allow guests to request booking cancellation online while enforcing a strict 48-hour refund eligibility rule.
- **User / Actors**: Customer (request), Manager/Admin (approve/reject).
- **Preconditions**: Booking status is `pending` or `confirmed`.
- **Process**:
  1. *Guest Request*: Checks if current time is strictly more than 48 hours prior to check-in start of day:
     $$\text{now}() < \text{check\_in.startOfDay}() - 48\text{ hours}$$
     If eligible, updates status to `cancellation_requested` and records the originating status in the history note (`Cancellation requested from:confirmed.`).
  2. *Staff Approval*: Updates status to `cancelled` and automatically transitions all `paid` deposit payments to `refunded`, stamping them with transaction ID `RFD-{payment_id}-{date}`.
  3. *Staff Rejection*: Parses the prior status from history notes and restores the booking back to `pending` or `confirmed`.
- **Error Handling**: Rejects requests made within 48 hours of check-in with HTTP 422: `"Cancellation requests are only accepted more than 48 hours before check-in."`
- **Related API Endpoints**:
  - `POST /api/v1/bookings/{id}/request-cancellation`
  - `POST /api/v1/bookings/{id}/approve-cancellation`
  - `POST /api/v1/bookings/{id}/reject-cancellation`
- **Related Database Tables**: `bookings`, `booking_status_histories`, `payments`

---

## 6.4 Payments & Invoicing

### Feature 15: Payment Recording & Balance Settlement
- **Purpose**: Record guest payments (deposits or final balance settlements) against bookings.
- **User / Actors**: Front-Desk Staff, Managers, Administrators.
- **Preconditions**: Booking must not be `cancelled` or `completed`; payment amount must not exceed remaining unpaid balance.
- **Input**:
  - `booking_id` (integer)
  - `amount` (numeric, 0.01 to 9999999999.99)
  - `payment_method` (`cash`, `card`, `bank_transfer`, `online`, `aba`, `wing`, `acleda`)
  - `transaction_id` (optional string, max 150)
- **Process**:
  1. System checks existing payments with status `pending` or `paid`.
  2. Calculates remaining balance: $\text{remaining} = \text{total\_amount} - \sum \text{amount}$.
  3. If $\text{amount} > \text{remaining}$, rejects transaction with HTTP 422.
  4. Auto-generates `TXN-YYYYMMDD-XXXXXX` if `transaction_id` was left blank.
  5. If method is `cash`, marks payment `paid` immediately; otherwise marks `pending`.
- **Related API Endpoints**: `POST /api/v1/payments`, `POST /api/v1/bookings/{id}/payments`
- **Related Database Tables**: `payments`, `bookings`

---

### Feature 16: Deposit Payment (Customer Portal)
- **Purpose**: Allow guests to confirm and submit deposit payment proof online using QR codes or card transfer.
- **User / Actors**: Authenticated Customer owning the booking.
- **Input**: `payment_method` (`card`, `aba`, `wing`, `acleda`), `transaction_id` (string).
- **Process**:
  1. Verifies that the authenticated user owns the booking's guest record.
  2. Creates a payment with `amount = booking.deposit_amount`.
  3. Automatically marks payment as `paid` with `paid_at = now()`.
- **Related API Endpoints**: `POST /api/v1/bookings/{id}/deposit-payment`
- **Related Database Tables**: `payments`, `bookings`

---

### Feature 17: Payment Lifecycle & Refund Processing
- **Purpose**: Allow managers to transition recorded payments to `paid`, `failed`, or `refunded`.
- **User / Actors**: Managers, Administrators (`payments.update`, `payments.refund`).
- **Transitions**:
  - `pending` → `paid` (records `paid_at = now()` and updates transaction ID).
  - `pending` → `failed`.
  - `paid` → `refunded` (assigns refund transaction ID).
- **Related API Endpoints**: `POST /api/v1/payments/{id}/paid`, `POST /api/v1/payments/{id}/failed`, `POST /api/v1/payments/{id}/refund`
- **Related Database Tables**: `payments`

---

### Feature 18: Client-Side PDF Invoice Generation
- **Purpose**: Generate and download a branded, printable stay invoice with reservation breakdown, guest details, and QR codes.
- **User / Actors**: Guests, Front-Desk Staff.
- **Technology**: Customer website utility using `jspdf` and `html2canvas-pro`.
- **Output**: Downloaded PDF file formatted with hotel header, dates, room details, payment history, and booking reference code.

---

## 6.5 Promotional Discounts & Coupons

### Feature 19: Coupon Management & Validation
- **Purpose**: Configure marketing discounts (percentage or flat) and validate them in real-time during customer checkout.
- **User / Actors**: Managers/Admins (management), Public Guests (lookup/validation).
- **Input**: `code` (string), `discount_type` (`percentage`, `fixed`), `discount_value`, `min_amount`, `start_date`, `end_date`, `usage_limit`, `status`.
- **Validation Rules**:
  - Current timestamp must fall within `[start_date, end_date]`.
  - Stay base total must be $\ge \text{min\_amount}$.
  - Coupon `status` must be `'active'`.
  - Total redemptions must not exceed `usage_limit`.
- **Related API Endpoints**: `GET /api/v1/coupons/active`, `GET /api/v1/coupons/validate/{code}`, `GET /api/v1/coupons`, `POST /api/v1/coupons`, `PUT /api/v1/coupons/{id}`, `DELETE /api/v1/coupons/{id}`
- **Related Database Tables**: `coupons`, `bookings`

---

## 6.6 Guest Feedback & Reviews

### Feature 20: Guest Review Submission & Moderation
- **Purpose**: Enable guests to review their stays, and provide a moderation workflow for hotel managers before publishing reviews.
- **User / Actors**: Customers (submit/update/delete own), Managers/Admins (approve/reject).
- **Input**: `booking_id`, `guest_id`, `rating` (integer 1-5), `comment` (text).
- **Process**:
  1. Customer submits review; initially stored with `status = 'pending'`.
  2. Public `GET /api/v1/reviews/approved` endpoint strictly filters `WHERE status = 'approved'`.
  3. Managers review submissions and trigger `approve` or `reject` actions.
- **Related API Endpoints**: `GET /api/v1/reviews/approved`, `GET /api/v1/reviews`, `POST /api/v1/reviews`, `POST /api/v1/reviews/{id}/approve`, `POST /api/v1/reviews/{id}/reject`
- **Related Database Tables**: `reviews`, `bookings`, `guests`

---

## 6.7 Operations, Analytics & Administration

### Feature 21: Staff Management & Access Delegation
- **Purpose**: Administer employee accounts, assign positions, track employment dates, and delegate system roles.
- **User / Actors**: Managers, Administrators (`staff.*` permissions).
- **Input**: `name`, `email`, `phone`, `password`, `role` (`admin`, `manager`, `staff`), `employee_id`, `position`, `hire_date`, `photo` (file).
- **Process**: Creates user record, assigns Spatie role, creates `staff` record, and uploads photo to Cloudinary.
- **Related API Endpoints**: `GET /api/v1/staff`, `POST /api/v1/staff`, `GET /api/v1/staff/{id}`, `PUT /api/v1/staff/{id}`, `DELETE /api/v1/staff/{id}`, `POST /api/v1/staff/{id}/photo`
- **Related Database Tables**: `users`, `staff`, `model_has_roles`

---

### Feature 22: Executive Dashboard & Analytics
- **Purpose**: Provide real-time KPIs, occupancy rates, and revenue analytics for hotel leadership.
- **User / Actors**: Managers, Administrators (`dashboard.view` permission).
- **Metrics Calculated**:
  - `total_rooms`, `available_rooms`, `occupied_rooms`
  - `today_bookings`, `total_guests`
  - `today_revenue`, `total_revenue`
  - Revenue breakdown charts: Today, This Week, This Month, This Year.
- **Performance / Caching**: Results are cached in Redis under `dashboard:summary` with a 60-second TTL. The Admin Console polls this endpoint every 15 seconds.
- **Related API Endpoints**: `GET /api/v1/dashboard/summary`
- **Related Database Tables / Cache**: Redis cache store, `v_occupancy_summary`, `v_revenue_summary`, `v_booking_summary`

---

### Feature 23: Data Export (CSV Streams)
- **Purpose**: Stream large datasets directly to CSV files for accounting and external reporting.
- **User / Actors**: Managers, Administrators (`dashboard.view` permission).
- **Export Streams**:
  - `GET /api/v1/exports/bookings`: Exports booking codes, guest names, dates, amounts, sources, and statuses.
  - `GET /api/v1/exports/guests`: Exports guest names, contact info, nationalities, and ID numbers.
  - `GET /api/v1/exports/payments`: Exports payment amounts, methods, transaction IDs, statuses, and payment dates.
- **Implementation**: Utilizes `Symfony\Component\HttpFoundation\StreamedResponse` to stream rows directly from cursor queries, avoiding memory exhaustion.
