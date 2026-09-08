# 04 · System Design

## 4.1 Overall System Architecture

The **Hotel Management System** is architected as a decoupled, multi-tier client-server application hosted within a containerized monorepo. The core domain logic, persistence, and state transitions reside exclusively inside the **Backend API**, while user interactions are partitioned between two dedicated Single-Page Applications (SPAs): the **Customer Website** and the **Admin Console**.

```mermaid
flowchart TB
    subgraph Clients["Client Tier (Browsers)"]
        GuestUser["Guest / Visitor<br/>(Desktop / Mobile)"]
        StaffUser["Front Desk / Manager / Admin<br/>(Workstations / Tablets)"]
    end

    subgraph Presentation["Presentation Tier (Nginx Gateway :8000)"]
        NGINX["Nginx HTTP Server<br/>(CORS, Static Proxy, FastCGI Router)"]
    end

    subgraph ApplicationTier["Application Tier (Docker Containers)"]
        PHP["PHP-FPM Application Engine<br/>(Laravel 13 · PHP 8.4)"]
        Worker["Queue Worker Container<br/>(artisan queue:work redis)"]
        Scheduler["Scheduler Container<br/>(artisan schedule:work)"]
    end

    subgraph DataTier["Data & Cache Tier"]
        PG[("PostgreSQL 18<br/>(Port 5433 host / 5432 internal)<br/>hotel_management")]
        RD[("Redis 8-Alpine<br/>(Port 6379)<br/>Cache, Queue & Session Store")]
    end

    subgraph ExternalServices["External Services"]
        Cloudinary["Cloudinary CDN<br/>(Room & Profile Images)"]
        MailService["SMTP / Log Mailer<br/>(OTP & Notifications)"]
    end

    GuestUser -- "HTTPS / JSON" --> NGINX
    StaffUser -- "HTTPS / JSON" --> NGINX
    NGINX -- "FastCGI :9000" --> PHP
    PHP --> PG
    PHP --> RD
    PHP --> Cloudinary
    Worker --> RD
    Worker --> MailService
    Scheduler --> PHP
```

### Monorepo Topology
```text
hotel-management-system/
├── backend/            # Laravel 13 REST API (/api/v1)
├── frontend-customer/  # React 19 Guest Portal
├── frontend-admin/     # React 19 Staff / Admin Console
├── nginx/              # Nginx reverse proxy configuration
├── docker/             # Database initialization and container scripts
├── backups/            # Automated pg_dump database snapshots
├── scripts/            # PowerShell backup/restore utilities
└── docs/               # Technical documentation (Sections 01–11)
```

---

## 4.2 Backend Architecture

The backend follows an enterprise layered architecture combining the **Service-Repository Pattern**, **Data Transfer Objects (DTOs)**, **Form Requests**, and **API Resources**.

```mermaid
flowchart TD
    Req["Incoming HTTP Request"] --> Route["API Route (routes/api.php)"]
    Route --> Throttler["Rate Limiter Middleware"]
    Throttler --> Sanctum["Sanctum Auth & Spatie Permission Middleware"]
    Sanctum --> FormReq["Form Request (Validation & Authorization)"]
    FormReq --> Controller["API Controller (app/Http/Controllers/Api/V1/...)"]
    Controller --> DTO["DTO (Data Transfer Object)"]
    Controller --> Service["Domain Service (app/Services/...)"]
    Service --> Repo["Repository (app/Repositories/...)"]
    Repo --> Model["Eloquent Model (app/Models/...)"]
    Model --> DB[("PostgreSQL 18 Database")]
    Service --> Job["Queued Job (app/Jobs/...)"]
    Job --> RedisQ[("Redis 8 Queue")]
    Controller --> Res["API Resource (app/Http/Resources/Api/V1/...)"]
    Res --> Output["JSON Response Envelope"]
```

### Architectural Layer Responsibilities
1. **Form Requests (`app/Http/Requests`)**:
   Enforce input validation rules (types, formats, existence, array structures) and policy-level route authorization prior to hitting controller methods.
2. **Controllers (`app/Http/Controllers/Api/V1`)**:
   Thin orchestration layer responsible for unpacking validated request payloads, invoking domain services, and returning structured JSON resources.
3. **Data Transfer Objects (`app/DTOs`)**:
   Strongly typed data carriers (such as `CreateBookingData`, `CreatePaymentData`) ensuring consistency between controllers and internal services.
4. **Domain Services (`app/Services`)**:
   Encapsulate business logic, database transactions (`DB::transaction`), row locking (`lockForUpdate`), date range overlap calculations, discount applications, and event job dispatching.
5. **Repositories (`app/Repositories`)**:
   Abstract database read and write queries, ensuring clean separation between SQL queries and business logic.
6. **API Resources (`app/Http/Resources`)**:
   Transform Eloquent models and relations into consistent, predictable JSON response formats with formatted dates and nested objects.

---

## 4.3 Customer Frontend Architecture

The **Customer Website** (`frontend-customer/`) is built on **React 19.2**, bundled with **Vite 8**, and styled using **Tailwind CSS 4** and **shadcn/ui** primitives.

```mermaid
flowchart TD
    subgraph UIComponents["Presentation Layer"]
        Layout["SiteLayout & AuthLayout"]
        Pages["Pages (Home, Rooms, Booking, Payment, Profile)"]
        Widgets["Interactive Widgets (Search, DatePicker, Currency Toggle)"]
    end

    subgraph StateAndCache["State & Cache Layer"]
        AuthStore["Zustand authStore (User, Token, Persisted in localStorage)"]
        BookingStore["Zustand bookingStore (Dates, Selected Rooms, Coupon)"]
        ReactQuery["TanStack React Query 5 (Server State, Room Cache, Availability)"]
    end

    subgraph DataServices["Service Layer"]
        ApiClient["Axios Instance (Auth Interceptors, Bearer Token Injection)"]
        ApiModules["API Services (rooms.js, bookings.js, coupons.js, authApi.js)"]
    end

    subgraph Utilities["Utilities & Integration"]
        PricingLib["pricing.js (Nights, Totals, Deposit Estimation)"]
        InvoiceGen["jspdf + html2canvas-pro (Client PDF Render)"]
        I18n["i18n Context (EN / KH Translations)"]
        EmailJS["@emailjs/browser (Direct Contact Form Delivery)"]
    end

    Pages --> UIComponents
    Pages --> ReactQuery
    Pages --> BookingStore
    Pages --> AuthStore
    ReactQuery --> ApiModules
    ApiModules --> ApiClient
    Pages --> PricingLib
    Pages --> InvoiceGen
    Pages --> I18n
```

### State Management Strategy
- **Client Session (`authStore.js`)**: Tracks current user object, bearer token, authentication status, and provides `login()`, `logout()`, `updateUser()`. Persisted via Zustand `persist` middleware in `localStorage`.
- **Booking Draft (`bookingStore.js`)**: Maintains ongoing reservation draft state across multi-step wizard screens: selected check-in/out dates, adult/child counts, selected room IDs, and applied coupon object.
- **Server Cache (TanStack Query 5)**: Manages caching, background invalidation, and deduplication of room catalogs, active coupons, and approved reviews.

---

## 4.4 Admin Frontend Architecture

The **Admin Console** (`frontend-admin/`) provides a secure back-office operations panel with role-based navigation and protected route boundaries.

```mermaid
flowchart TD
    Router["React Router 7 Navigation"] --> Guard{"ProtectedRoute (Token Present?)"}
    Guard -- No --> LoginView["/login View"]
    Guard -- Yes --> RoleGuard{"RoleRoute (User in Allowed Roles?)"}
    RoleGuard -- No --> RedirectDashboard["Redirect to /dashboard"]
    RoleGuard -- Yes --> Layout["MainLayout (Header, Role-Aware Sidebar)"]

    Layout --> Views["Admin Views"]
    Views --> DPage["Dashboard (KPI Cards, SVG Revenue Chart)"]
    Views --> BPage["Bookings (Filter, Search, Lifecycle Action Modals)"]
    Views --> RPage["Rooms & Room Types (Inventory & Status Toggles)"]
    Views --> PPage["Payments (Payment Recording, Balance Settlement)"]
    Views --> GPage["Guests (Guest List & ID Inspection)"]
    Views --> SPage["Staff (Employee Accounts - Admin/Manager Only)"]
```

### Role-Based Access Control in Admin Console
- **`ProtectedRoute`**: Verifies existence of a valid Sanctum bearer token in `authStore`. Automatically redirects unauthenticated users to `/login`.
- **`RoleRoute`**: Accepts allowed role arrays (e.g. `roles={["admin", "manager"]}`). Restricts sensitive modules like `/staff` from standard front-desk personnel.
- **Dynamic Navigation Sidebar**: Conditionally renders menu items based on `user.roles` array.

---

## 4.5 API Architecture & Standards

The system exposes a unified JSON REST API under `/api/v1`.

### Request & Response Standards
- **Endpoint Prefix**: All endpoints are mounted under `/api/v1/`.
- **Headers**:
  ```http
  Accept: application/json
  Content-Type: application/json
  Authorization: Bearer <sanctum_token>
  ```
- **Standard Successful Response Format**:
  ```json
  {
    "data": { ... }
  }
  ```
- **Standard Error Response Format (HTTP 422 Validation Error)**:
  ```json
  {
    "message": "The given data was invalid.",
    "errors": {
      "check_in": ["Check-in cannot be in the past."],
      "room_ids": ["At least one room must be selected."]
    }
  }
  ```

---

## 4.6 Authentication Architecture

The system implements stateless token-based authentication via **Laravel Sanctum**, coupled with an **Email OTP (One-Time Password)** verification mechanism.

```mermaid
sequenceDiagram
    autonumber
    actor Guest as Customer Browser
    participant API as Laravel Auth API
    participant DB as PostgreSQL 18
    participant Redis as Redis 8
    participant Mail as Queued Mailer

    Note over Guest, Mail: Registration Flow
    Guest->>API: POST /api/v1/auth/register (name, email, phone, password, photo)
    API->>API: Validate input & hash password
    API->>API: Generate 6-digit cryptographic OTP (random_int)
    API->>DB: Store registration draft & otp_hash (RegistrationOtp table)
    API->>Mail: Queue RegistrationOtpMail with raw OTP
    API-->>Guest: 201 Created (verification_id, expires_at: 10 mins)
    Mail-->>Guest: Email arrives with 6-digit code

    Note over Guest, Mail: OTP Verification Flow
    Guest->>API: POST /api/v1/auth/verify-otp (verification_id, otp)
    API->>DB: Lock registration row & verify Hash::check(otp, otp_hash)
    API->>DB: Create User record (role = 'customer')
    API->>DB: Create Guest record with photo & details
    API->>DB: Generate Sanctum Bearer Token (customer_auth)
    API->>DB: Delete temporary RegistrationOtp row
    API-->>Guest: 200 OK (user object, bearer token)
    Guest->>Guest: Store token in Zustand localStorage
```

### Password Reset Flow
1. User requests password reset via `POST /api/v1/auth/password/forgot` with email.
2. System generates a 6-digit OTP, stores its bcrypt hash in Redis under `password_reset:v2:{email}` with a 10-minute TTL and max 5 attempts.
3. System emails the code to the user via `RegistrationOtpMail`.
4. User submits new password and code via `POST /api/v1/auth/password/reset`.
5. Redis validates attempt count and OTP hash; updates user password; deletes Redis key.

---

## 4.7 Authorization Architecture

Authorization is governed by **Spatie Laravel-Permission (`spatie/laravel-permission`)** using database-backed roles and permissions cached in Redis.

```mermaid
flowchart LR
    User["User Model"] --> UserRoles["model_has_roles"]
    UserRoles --> Roles["Role Model<br/>(admin, manager, staff, customer)"]
    Roles --> RolePerms["role_has_permissions"]
    RolePerms --> Permissions["Permission Model<br/>(38 distinct permissions)"]
    Permissions --> Middleware["Laravel Route Middleware<br/>(permission:bookings.confirm, etc.)"]
```

### Role Hierarchy & Permissions Summary
- **`admin`**: Assigned all 38 system permissions unconditionally.
- **`manager`**: Assigned all operational permissions, room management, pricing, coupons, payments, refunds, and staff accounts.
- **`staff`**: Restricted to read operations on rooms, amenities, bookings, payments, and guests (`*.view`).
- **`customer`**: Granted `bookings.create`, `reviews.create`, `reviews.update`, `reviews.delete`. Write endpoints strictly enforce ownership checks.

---

## 4.8 End-to-End Sequence Diagrams

### 4.8.1 Online Booking & Deposit Sequence

```mermaid
sequenceDiagram
    autonumber
    actor Customer as Customer SPA
    participant Controller as BookingController
    participant Svc as BookingService
    participant Avail as AvailabilityService
    participant Price as BookingPriceService
    participant DB as PostgreSQL
    participant PaySvc as PaymentService

    Customer->>Controller: POST /api/v1/bookings (dates, room_ids, guest_id, coupon_id)
    Controller->>Svc: create(bookingData)
    Svc->>DB: Begin Transaction & lock rooms (SELECT ... FOR UPDATE)
    Svc->>Avail: checkOverlap(room_ids, check_in, check_out)
    Avail-->>Svc: false (no conflict)
    Svc->>Price: calculateBaseTotal(rooms, nights)
    Svc->>Price: calculateFinalTotal(baseTotal, coupon)
    Svc->>Svc: depositRateForGuest(guest) -> 20% (KH) / 30% (Intl)
    Svc->>DB: INSERT into bookings (booking_code: BK-..., status: 'pending')
    Svc->>DB: INSERT into booking_items (rooms, nights, subtotal)
    Svc->>DB: INSERT into booking_status_histories ('pending')
    Svc-->>Controller: Booking instance
    Controller-->>Customer: 201 Created (BookingResource)

    Note over Customer, PaySvc: Deposit Payment
    Customer->>Controller: POST /api/v1/bookings/{id}/deposit-payment (method, txn_id)
    Controller->>PaySvc: create([amount: deposit_amount, status: 'pending'])
    PaySvc->>DB: INSERT into payments (amount, method, txn_id, status: 'pending')
    Controller->>PaySvc: markPaid(payment)
    PaySvc->>DB: UPDATE payments SET status = 'paid', paid_at = now()
    PaySvc-->>Customer: 201 Created (PaymentResource)
```

### 4.8.2 Check-In Sequence Flow

```mermaid
sequenceDiagram
    autonumber
    actor Staff as Front-Desk Staff
    participant AdminApp as Admin Console
    participant BookingCtrl as BookingController
    participant Svc as BookingService
    participant DB as PostgreSQL
    participant Trigger as DB Trigger trg_bookings_status_history
    participant Job as SendBookingStatusEmail

    Staff->>AdminApp: Click "Check In" on confirmed booking
    AdminApp->>BookingCtrl: POST /api/v1/bookings/{id}/check-in
    BookingCtrl->>Svc: checkIn(booking, changedBy)
    Svc->>DB: SELECT booking FOR UPDATE
    Svc->>Svc: Validate status transition ('confirmed' -> 'in_house')
    Svc->>DB: UPDATE bookings SET status = 'in_house'
    DB->>Trigger: Execute trg_record_booking_status_change()
    Trigger->>DB: INSERT into booking_status_histories (status: 'in_house')
    Svc->>Job: Dispatch SendBookingStatusEmail('in_house') after commit
    Svc-->>BookingCtrl: Refreshed booking
    BookingCtrl-->>AdminApp: 200 OK (BookingResource)
    AdminApp-->>Staff: Display green "In House" badge
```

### 4.8.3 Redis Queue & Scheduled Job Architecture

```mermaid
flowchart LR
    subgraph SchedSub["Scheduled Work (Every Minute)"]
        ArtisanCron["artisan schedule:work"] --> CheckTask{"Daily Midnight?"}
        CheckTask -- Yes --> JobPurge["DeleteCancelledBookings Job"]
        JobPurge --> QueryOld["Query bookings WHERE status='cancelled'<br/>AND updated_at < retention_window"]
        QueryOld --> DeleteOld["Delete from bookings & booking_items"]
    end

    subgraph QueueSub["Queue Work (Continuous Loop)"]
        AppEvent["Application Events (Booking Confirmed / Status Changed)"] --> DispatchJob["Job::dispatch()->afterCommit()"]
        DispatchJob --> RedisList[("Redis Queue: default")]
        WorkerLoop["artisan queue:work redis<br/>--tries=3 --timeout=90"] --> PopJob["LPOP / BRPOP job payload"]
        RedisList --> WorkerLoop
        WorkerLoop --> MailMailable["Mail::to()->send(Mailable)"]
        MailMailable --> SMTPLog["Mail Driver (Log / SMTP)"]
    end
```

---

## 4.9 Security Architecture

| Security Domain | Mitigation Mechanism | Code Implementation |
| --- | --- | --- |
| **Authentication** | Bearer token authentication via Laravel Sanctum; tokens are sha256 hashed in database and revoked on logout. | `AuthController::logout`, `Sanctum` middleware |
| **Brute Force Protection** | Redis-backed IP/Account rate limiters on sensitive endpoints: 5 attempts/min on login and OTP generation; 10 attempts/min on booking/payment creation. | `throttle:login`, `throttle:otp`, `throttle:booking-create` in `bootstrap/app.php` |
| **Password Security** | Passwords hashed using Bcrypt with default cost parameters; plaintext passwords never logged or stored. | `Hash::make()` in `RegistrationService` & `PasswordResetService` |
| **OTP Security** | One-time passwords are never stored in plaintext; only `Hash::make($otp)` is saved in database or Redis; 10-minute expiry; 5 failed attempt threshold. | `RegistrationOtp::$otp_hash`, `PasswordResetService` |
| **SQL Injection** | Strict usage of Eloquent ORM and parameterized PDO bindings; all raw statements in migrations use parameterized queries. | `AvailabilityService`, `BookingRepository` |
| **Concurrency & Race Conditions** | Row-level pessimistic locking (`SELECT ... FOR UPDATE`) during booking creation and status transitions prevents concurrent double-bookings. | `Room::lockForUpdate()`, `Booking::lockForUpdate()` |
| **Cross-Origin Resource Sharing (CORS)** | Strict CORS origin policy restricting API access exclusively to trusted frontends (`FRONTEND_URL`). | `config/cors.php` |
| **Cross-Tenant Authorization** | Customers can only book for or view their own guest records; verified through custom validation rules and model policies. | `StoreBookingRequest::after`, `BookingPolicy` |
