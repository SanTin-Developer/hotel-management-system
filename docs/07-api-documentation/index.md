# 07 · API Documentation

This document provides complete, reference-grade technical documentation for all RESTful API endpoints exposed under `/api/v1/`.

---

## 7.1 Global Standards & Conventions

### Base URL & Protocol
- **Local / Docker Gateway**: `http://localhost:8000/api/v1`
- **Standard Headers**:
  ```http
  Accept: application/json
  Content-Type: application/json
  Authorization: Bearer <sanctum_token>
  ```

### HTTP Status Code Convention
| Status Code | Meaning | Description |
| --- | --- | --- |
| **200 OK** | Success | Request succeeded; response payload returned in `data`. |
| **201 Created** | Resource Created | Entity persisted; newly created record returned. |
| **204 No Content** | Action Executed | Successful execution with no return body. |
| **401 Unauthorized** | Missing / Invalid Token | Bearer token missing, expired, or invalid. |
| **403 Forbidden** | Insufficient Permissions | User lacks required role/permission or cross-tenant ownership. |
| **404 Not Found** | Resource Missing | Specified ID or slug does not exist. |
| **409 Conflict** | State Conflict | Foreign key constraint violation (e.g. deleting occupied room). |
| **422 Unprocessable Content** | Validation Failed | Input format, type, or business rule validation error. |
| **429 Too Many Requests** | Rate Limited | Throttling limit exceeded on login, OTP, or bookings. |

---

## 7.2 Authentication Endpoints

### 1. Register Account
`POST /api/v1/auth/register`
- **Auth**: Public (`throttle:public-api`)
- **Headers**: `Content-Type: multipart/form-data`
- **Request Body (form-data)**:
  - `full_name` (required, string, max:150)
  - `email` (required, email, max:255, unique in `users` and `registration_otps`)
  - `phone` (required, string, max:30)
  - `password` (required, string, min:8)
  - `password_confirmation` (required, matching `password`)
  - `country` (required, string, max:100)
  - `id_type` (nullable, in: `passport`, `national_id`, `driving_license`)
  - `id_number` (nullable, string, max:100)
  - `photo` (nullable, image file: jpeg, png, webp, max:5120 KB)
- **Response (201 Created)**:
  ```json
  {
    "message": "Registration OTP sent to your email.",
    "verification_id": 14,
    "email": "c***r@example.com",
    "expires_at": "2026-09-08T12:00:00.000000Z"
  }
  ```
- **Error (422)**:
  ```json
  {
    "message": "The email has already been taken.",
    "errors": {
      "email": ["An account with this email already exists. Please login instead."]
    }
  }
  ```

---

### 2. Verify OTP & Activate Account
`POST /api/v1/auth/verify-otp`
- **Auth**: Public (`throttle:otp`)
- **Request Body**:
  ```json
  {
    "verification_id": 14,
    "otp": "491823"
  }
  ```
- **Validation Rules**: `verification_id` (required, integer), `otp` (required, string, size:6).
- **Response (200 OK)**:
  ```json
  {
    "message": "Registration verified successfully.",
    "user": {
      "id": 5,
      "name": "Sokha Chan",
      "email": "sokha@example.com",
      "phone": "012345678",
      "status": "active",
      "roles": [
        { "id": 4, "name": "customer" }
      ],
      "guest": {
        "id": 8,
        "full_name": "Sokha Chan",
        "email": "sokha@example.com",
        "phone": "012345678",
        "country": "Cambodia",
        "photo_url": "https://res.cloudinary.com/.../avatar.jpg"
      }
    },
    "token": "1|e4b789af...plainTextToken"
  }
  ```
- **Error (422)**:
  ```json
  {
    "message": "The provided OTP is incorrect.",
    "errors": {
      "otp": ["The provided OTP is incorrect."]
    }
  }
  ```

---

### 3. Resend Registration OTP
`POST /api/v1/auth/resend-otp`
- **Auth**: Public (`throttle:otp`)
- **Request Body**: `{ "verification_id": 14 }`
- **Response (200 OK)**:
  ```json
  {
    "message": "A new OTP has been sent to your email.",
    "verification_id": 14,
    "email": "s***a@example.com",
    "expires_at": "2026-09-08T12:10:00.000000Z"
  }
  ```

---

### 4. User Login
`POST /api/v1/auth/login`
- **Auth**: Public (`throttle:login`)
- **Request Body**:
  ```json
  {
    "email": "sokha@example.com",
    "password": "password123"
  }
  ```
  *(Note: The `email` field accepts either an email address OR a phone number).*
- **Response (200 OK)**:
  ```json
  {
    "message": "Login successful.",
    "user": {
      "id": 5,
      "name": "Sokha Chan",
      "email": "sokha@example.com",
      "phone": "012345678",
      "status": "active",
      "roles": [{ "name": "customer" }],
      "guest": { "id": 8, "country": "Cambodia" }
    },
    "token": "2|99a2c1...bearerToken"
  }
  ```
- **Error (401 / 404 / 403)**:
  - 404: `{ "message": "No account found with this email or phone. Please register first." }`
  - 401: `{ "message": "Incorrect password. Please try again." }`
  - 403: `{ "message": "Your account is inactive. Please contact support." }`

---

### 5. Logout
`POST /api/v1/auth/logout`
- **Auth**: `auth:sanctum`
- **Response (200 OK)**:
  ```json
  {
    "message": "Logged out successfully."
  }
  ```

---

### 6. Get Authenticated User Profile
`GET /api/v1/auth/me`
- **Auth**: `auth:sanctum`
- **Response (200 OK)**: Returns full user entity with roles, permissions, and attached guest/staff profile.

---

### 7. Update User Profile
`PUT /api/v1/auth/me`
- **Auth**: `auth:sanctum`
- **Request Body**:
  ```json
  {
    "full_name": "Sokha Chan",
    "phone": "012345678",
    "address": "Street 271, Phnom Penh",
    "nationality": "Cambodian",
    "country": "Cambodia"
  }
  ```
- **Response (200 OK)**: `{ "user": { ... } }`

---

### 8. Upload / Remove Profile Photo
- `POST /api/v1/auth/me/photo`: Uploads `photo` file to Cloudinary; returns updated user.
- `DELETE /api/v1/auth/me/photo`: Deletes Cloudinary asset and clears photo columns; returns updated user.

---

## 7.3 Password Recovery Endpoints

### 9. Forgot Password (Request Code)
`POST /api/v1/auth/password/forgot`
- **Auth**: Public (`throttle:otp`)
- **Request Body**: `{ "email": "sokha@example.com" }`
- **Response (200 OK)**:
  ```json
  {
    "message": "If the email exists, a reset code has been sent."
  }
  ```

---

### 10. Reset Password with OTP
`POST /api/v1/auth/password/reset`
- **Auth**: Public (`throttle:otp`)
- **Request Body**:
  ```json
  {
    "email": "sokha@example.com",
    "otp": "837192",
    "password": "newSecurePassword123",
    "password_confirmation": "newSecurePassword123"
  }
  ```
- **Response (200 OK)**:
  ```json
  {
    "message": "Password has been reset successfully."
  }
  ```

---

## 7.4 Room Types Endpoints

### 11. List Room Types
`GET /api/v1/room-types`
- **Auth**: Public (`throttle:public-api`)
- **Query Parameters**: `status` (active/inactive), `per_page`, `sort`
- **Response (200 OK)**:
  ```json
  {
    "data": [
      {
        "id": 1,
        "name": "Deluxe King Suite",
        "description": "Spacious suite with city views and king bed.",
        "capacity": 2,
        "base_price": 85.00,
        "size": 42.50,
        "bed_type": "1 King Bed",
        "image_url": "https://res.cloudinary.com/.../deluxe.jpg",
        "status": "active"
      }
    ]
  }
  ```

---

### 12. Create Room Type
`POST /api/v1/room-types`
- **Auth**: `auth:sanctum` + `permission:room-types.create`
- **Request Body**:
  ```json
  {
    "name": "Premier Executive Suite",
    "description": "Top-floor luxury suite.",
    "capacity": 3,
    "base_price": 140.00,
    "size": 65.00,
    "bed_type": "1 Super King",
    "status": "active"
  }
  ```
- **Response (201 Created)**: Returns created `RoomTypeResource`.

---

### 13. Update / Delete Room Type
- `PUT /api/v1/room-types/{id}`: `auth:sanctum` + `permission:room-types.update`
- `DELETE /api/v1/room-types/{id}`: `auth:sanctum` + `permission:room-types.delete`
- `POST /api/v1/room-types/{id}/image`: Uploads category featured image.
- `DELETE /api/v1/room-types/{id}/image`: Clears category image.

---

## 7.5 Rooms Endpoints

### 14. List Rooms
`GET /api/v1/rooms`
- **Auth**: Public (`throttle:public-api`)
- **Query Filters**: `status`, `floor`, `room_type_id`, `search`
- **Response (200 OK)**: Returns array of rooms with attached `room_type`, `amenities`, and `images` gallery.

---

### 15. Create Room
`POST /api/v1/rooms`
- **Auth**: `auth:sanctum` + `permission:rooms.create`
- **Request Body**:
  ```json
  {
    "room_type_id": 1,
    "room_number": "301",
    "floor": 3,
    "status": "available",
    "description": "Quiet corner room facing courtyard"
  }
  ```
- **Response (201 Created)**: Returns created `RoomResource`.

---

### 16. Update Room & Amenities
- `PUT /api/v1/rooms/{id}`: Updates room attributes.
- `DELETE /api/v1/rooms/{id}`: Deletes room. If room has historical bookings, returns HTTP 409: `"This room cannot be deleted because it is still referenced by existing booking records."`
- `PUT /api/v1/rooms/{id}/amenities`: Body: `{ "amenity_ids": [1, 3, 5] }`. Synchronizes amenities.
- `DELETE /api/v1/rooms/{id}/amenities/{amenity}`: Removes single amenity.
- `POST /api/v1/rooms/{id}/image`: Uploads and appends gallery image to `room_images`.
- `DELETE /api/v1/rooms/{id}/images/{image}`: Deletes gallery photo.

---

### 17. Change Room Status
`PUT /api/v1/rooms/{id}/status`
- **Auth**: `auth:sanctum` + `permission:rooms.update`
- **Request Body**:
  ```json
  {
    "status": "cleaning",
    "note": "Housekeeping deep clean after check-out"
  }
  ```
- **Response (200 OK)**: Returns updated `RoomResource`.

---

## 7.6 Amenities Endpoints

### 18. List & CRUD Amenities
- `GET /api/v1/amenities`: Public list of amenities with `name_kh` and `description_kh`.
- `POST /api/v1/amenities`: `permission:amenities.create`.
  ```json
  {
    "name": "High-Speed Wi-Fi",
    "name_kh": "ប្រព័ន្ធអ៊ីនធឺណិតល្បឿនលឿន",
    "description": "Complimentary 100Mbps fiber internet in all rooms.",
    "description_kh": "សេវាអ៊ីនធឺណិតឥតគិតថ្លៃក្នុងបន្ទប់ទាំងអស់។",
    "icon": "wifi"
  }
  ```
- `PUT /api/v1/amenities/{id}`: `permission:amenities.update`.
- `DELETE /api/v1/amenities/{id}`: `permission:amenities.delete`.

---

## 7.7 Bookings & Availability Endpoints

### 19. Check Availability
`GET /api/v1/bookings/availability`
- **Auth**: Public (`throttle:availability`)
- **Query Parameters**:
  - `check_in` (required, date, format: YYYY-MM-DD, after_or_equal: today)
  - `check_out` (required, date, format: YYYY-MM-DD, after: check_in)
- **Response (200 OK)**:
  ```json
  {
    "data": [
      {
        "id": 101,
        "room_number": "101",
        "floor": 1,
        "status": "available",
        "room_type": {
          "id": 1,
          "name": "Deluxe King Suite",
          "base_price": "85.00"
        }
      }
    ]
  }
  ```

---

### 20. Availability Calendar Matrix
`GET /api/v1/bookings/calendar`
- **Auth**: Public (`throttle:availability`)
- **Query Parameters**: `check_in=YYYY-MM-DD`, `check_out=YYYY-MM-DD`
- **Response (200 OK)**: Returns calendar array mapping each room to an array of requested dates with boolean availability per day.

---

### 21. Create Booking
`POST /api/v1/bookings`
- **Auth**: `auth:sanctum` + `permission:bookings.create` (`throttle:booking-create`)
- **Request Body**:
  ```json
  {
    "guest_id": 8,
    "check_in": "2026-09-15",
    "check_out": "2026-09-18",
    "adults": 2,
    "children": 1,
    "room_ids": [101, 102],
    "coupon_id": 2,
    "special_request": "High floor room preferred, quiet side.",
    "booking_source": "website"
  }
  ```
- **Validation Rules**:
  - `guest_id`: required integer, exists in `guests,id`. Non-manager users are verified via `after()` validation to ensure ownership.
  - `check_in`: required date $\ge$ today.
  - `check_out`: required date $>$ `check_in`.
  - `room_ids`: required array of unique integer room IDs.
- **Response (201 Created)**:
  ```json
  {
    "data": {
      "id": 42,
      "booking_code": "BK-20260908-J8F3KQ",
      "check_in": "2026-09-15",
      "check_out": "2026-09-18",
      "nights": 3,
      "adults": 2,
      "children": 1,
      "total_amount": "459.00",
      "deposit_rate": "20.00",
      "deposit_amount": "91.80",
      "status": "pending",
      "rooms": [ ... ],
      "guest": { ... }
    }
  }
  ```

---

### 22. Booking Lifecycle Actions
- **Confirm**: `POST /api/v1/bookings/{id}/confirm` (`permission:bookings.confirm`)
- **Check-In**: `POST /api/v1/bookings/{id}/check-in` (`permission:bookings.checkin`)
  - Transitions status to `in_house`.
- **Complete**: `POST /api/v1/bookings/{id}/complete` (`permission:bookings.complete`)
  - Transitions status to `completed`.
- **Cancel**: `POST /api/v1/bookings/{id}/cancel` (`permission:bookings.cancel`)
  - Transitions status to `cancelled`. Triggers auto-refund of paid deposits if $>48\text{h}$ prior to check-in.

---

### 23. Booking Cancellation Request (Customer Self-Service)
`POST /api/v1/bookings/{id}/request-cancellation`
- **Auth**: `auth:sanctum` (must own booking)
- **Response (200 OK)**: Updates status to `cancellation_requested`.
- **Error (422)**: If $\le 48\text{h}$ prior to arrival:
  ```json
  {
    "message": "Cancellation requests are only accepted more than 48 hours before check-in.",
    "errors": {
      "status": ["Cancellation requests are only accepted more than 48 hours before check-in."]
    }
  }
  ```

---

### 24. Approve / Reject Cancellation Requests
- `POST /api/v1/bookings/{id}/approve-cancellation` (`permission:bookings.cancel`):
  Sets status to `cancelled` and marks paid deposits `refunded`.
- `POST /api/v1/bookings/{id}/reject-cancellation` (`permission:bookings.cancel`):
  Restores booking back to prior status (`pending` or `confirmed`).

---

### 25. Deposit Payment (Customer Self-Service)
`POST /api/v1/bookings/{id}/deposit-payment`
- **Auth**: `auth:sanctum` (must be booking guest or staff with `payments.create`)
- **Request Body**:
  ```json
  {
    "payment_method": "aba",
    "transaction_id": "ABA-TXN-9847120"
  }
  ```
- **Validation**: `payment_method` in: `card`, `aba`, `wing`, `acleda`; `transaction_id` required string max 150.
- **Response (201 Created)**: Automatically creates payment for exact `deposit_amount` and marks status `paid`.

---

## 7.8 Payments Endpoints

### 26. Standalone Record Payment (Admin Console)
`POST /api/v1/payments`
- **Auth**: `auth:sanctum` + `permission:payments.create` (`throttle:payment-create`)
- **Request Body**:
  ```json
  {
    "booking_id": 42,
    "amount": 367.20,
    "payment_method": "cash",
    "transaction_id": null
  }
  ```
- **Validation**:
  - `amount`: numeric $> 0$; cannot exceed remaining unpaid balance on booking.
  - `payment_method` in: `cash`, `card`, `bank_transfer`, `online`, `aba`, `wing`, `acleda`.
- **Response (201 Created)**:
  ```json
  {
    "data": {
      "id": 19,
      "booking_id": 42,
      "amount": "367.20",
      "payment_method": "cash",
      "transaction_id": "TXN-20260908-R9P1Q4",
      "status": "paid",
      "paid_at": "2026-09-08T12:05:00.000000Z"
    }
  }
  ```

---

### 27. Payment State Transitions
- `POST /api/v1/payments/{id}/paid`: `permission:payments.update`. Marks `paid` with `paid_at = now()`.
- `POST /api/v1/payments/{id}/failed`: `permission:payments.update`. Marks `failed`.
- `POST /api/v1/payments/{id}/refund`: `permission:payments.refund`. Marks `refunded`.

---

## 7.9 Guests Endpoints

### 28. Guests CRUD
- `GET /api/v1/guests`: `permission:guests.view`. Filter by `search` (name, email, phone).
- `GET /api/v1/guests/{id}`: `permission:guests.view`.
- `POST /api/v1/guests`: `permission:guests.create`.
- `PUT /api/v1/guests/{id}`: `permission:guests.update`.
- `DELETE /api/v1/guests/{id}`: `permission:guests.delete`.

---

## 7.10 Coupons Endpoints

### 29. Public Coupons & Validation
- `GET /api/v1/coupons/active`: Public list of currently valid coupons.
- `GET /api/v1/coupons/validate/{code}`: Validates code and returns discount values.
- `GET /api/v1/coupons`: `permission:coupons.view`.
- `POST /api/v1/coupons`: `permission:coupons.create`.
- `PUT /api/v1/coupons/{id}`: `permission:coupons.update`.
- `DELETE /api/v1/coupons/{id}`: `permission:coupons.delete`.

---

## 7.11 Reviews Endpoints

### 30. Reviews Submission & Moderation
- `GET /api/v1/reviews/approved`: Public approved reviews feed.
- `GET /api/v1/reviews`: `permission:reviews.view`. All reviews (pending, approved, rejected).
- `POST /api/v1/reviews`: `auth:sanctum`. Submits review (rating 1-5, comment).
- `POST /api/v1/reviews/{id}/approve`: `permission:reviews.approve`. Sets `approved`.
- `POST /api/v1/reviews/{id}/reject`: `permission:reviews.reject`. Sets `rejected`.

---

## 7.12 Staff Endpoints

### 31. Staff Account Management
- `GET /api/v1/staff`: `permission:staff.view`.
- `POST /api/v1/staff`: `permission:staff.create`. Creates user + role + employee details.
- `PUT /api/v1/staff/{id}`: `permission:staff.update`.
- `DELETE /api/v1/staff/{id}`: `permission:staff.delete`.
- `POST /api/v1/staff/{id}/photo`: Uploads employee photo.
- `DELETE /api/v1/staff/{id}/photo`: Clears employee photo.

---

## 7.13 Dashboard & Export Endpoints

### 32. Dashboard Summary
`GET /api/v1/dashboard/summary`
- **Auth**: `auth:sanctum` + `permission:dashboard.view`
- **Response (200 OK)**:
  ```json
  {
    "data": {
      "stats": {
        "total_rooms": 24,
        "today_bookings": 5,
        "total_guests": 82,
        "today_revenue": 425.00,
        "total_revenue": 14920.00,
        "available_rooms": 18,
        "occupied_rooms": 6
      },
      "revenue_chart": {
        "today": [ ... ],
        "this_week": [ ... ],
        "this_month": [ ... ],
        "this_year": [ ... ]
      },
      "booking_overview": { ... },
      "recent_bookings": [ ... ],
      "room_status": [ ... ]
    }
  }
  ```

---

### 33. Streamed CSV Exports
- `GET /api/v1/exports/bookings`: Query parameters: `status`, `search`. Returns `text/csv` stream.
- `GET /api/v1/exports/guests`: Query parameters: `search`. Returns `text/csv` stream.
- `GET /api/v1/exports/payments`: Query parameters: `status`. Returns `text/csv` stream.
