<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\PasswordController;
use App\Http\Controllers\Api\V1\Booking\BookingController;
use App\Http\Controllers\Api\V1\Booking\BookingPaymentController;
use App\Http\Controllers\Api\V1\Coupon\CouponController;
use App\Http\Controllers\Api\V1\Dashboard\DashboardController;
use App\Http\Controllers\Api\V1\Export\ExportController;
use App\Http\Controllers\Api\V1\Guest\GuestController;
use App\Http\Controllers\Api\V1\Payment\PaymentController;
use App\Http\Controllers\Api\V1\Review\ReviewController;
use App\Http\Controllers\Api\V1\Room\AmenityController;
use App\Http\Controllers\Api\V1\Room\RoomController;
use App\Http\Controllers\Api\V1\Room\RoomStatusController;
use App\Http\Controllers\Api\V1\Room\RoomTypeController;
use App\Http\Controllers\Api\V1\Staff\StaffController;
use Illuminate\Support\Facades\Route;

// Auth API-EndPoint
Route::prefix('v1/auth')->group(function () {

    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:public-api');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])
        ->middleware('throttle:otp');
    Route::post('/resend-otp', [AuthController::class, 'resendOtp'])
        ->middleware('throttle:otp');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/me', [AuthController::class, 'updateMe']);
        Route::post('/me/photo', [AuthController::class, 'uploadMePhoto']);
        Route::delete('/me/photo', [AuthController::class, 'removeMePhoto']);
    });
});

// Password Reset API-EndPoint
Route::prefix('v1/auth/password')->group(function () {

    Route::post('/forgot', [PasswordController::class, 'forgotPassword'])
        ->middleware('throttle:otp');
    Route::post('/reset', [PasswordController::class, 'resetPassword'])
        ->middleware('throttle:otp');
});

// Room Type API-EndPoint
Route::prefix('v1/room-types')->group(function () {

    Route::get('/', [RoomTypeController::class, 'index'])
        ->middleware('throttle:public-api');
    Route::get('/{roomType}', [RoomTypeController::class, 'show']);

    Route::middleware(['auth:sanctum', 'permission:room-types.create'])
        ->post('/', [RoomTypeController::class, 'store']);

    Route::middleware(['auth:sanctum', 'permission:room-types.update'])
        ->put('/{roomType}', [RoomTypeController::class, 'update']);

    Route::middleware(['auth:sanctum', 'permission:room-types.delete'])
        ->delete('/{roomType}', [RoomTypeController::class, 'destroy']);

    Route::middleware(['auth:sanctum', 'permission:room-types.update'])
        ->post('/{roomType}/image', [RoomTypeController::class, 'uploadImage']);

    Route::middleware(['auth:sanctum', 'permission:room-types.update'])
        ->delete('/{roomType}/image', [RoomTypeController::class, 'removeImage']);
});

// Room API-EndPoint
Route::prefix('v1/rooms')->group(function () {

    // Public
    Route::get('/', [RoomController::class, 'index'])
        ->middleware('throttle:public-api');
    Route::get('/{room}', [RoomController::class, 'show']);

    // Admin / Manager only
    Route::middleware(['auth:sanctum', 'permission:rooms.create'])
        ->post('/', [RoomController::class, 'store']);

    Route::middleware(['auth:sanctum', 'permission:rooms.update'])
        ->put('/{room}', [RoomController::class, 'update']);

    Route::middleware(['auth:sanctum', 'permission:rooms.delete'])
        ->delete('/{room}', [RoomController::class, 'destroy']);

    Route::middleware(['auth:sanctum', 'permission:rooms.update'])
        ->put('/{room}/amenities', [RoomController::class, 'syncAmenities']);

    Route::middleware(['auth:sanctum', 'permission:rooms.update'])
        ->delete('/{room}/amenities/{amenity}', [RoomController::class, 'removeAmenity']);

    Route::middleware(['auth:sanctum', 'permission:rooms.update'])
        ->post('/{room}/image', [RoomController::class, 'uploadImage']);

    Route::middleware(['auth:sanctum', 'permission:rooms.update'])
        ->delete('/{room}/images/{image}', [RoomController::class, 'removeImage']);

    Route::middleware(['auth:sanctum', 'permission:rooms.update'])
        ->put('/{room}/status', RoomStatusController::class);
});

// Amenities API-EndPoint
Route::prefix('v1/amenities')->group(function () {

    Route::get('/', [AmenityController::class, 'index'])
        ->middleware('throttle:public-api');
    Route::get('/{amenity}', [AmenityController::class, 'show']);

    Route::middleware(['auth:sanctum', 'permission:amenities.create'])
        ->post('/', [AmenityController::class, 'store']);

    Route::middleware(['auth:sanctum', 'permission:amenities.update'])
        ->put('/{amenity}', [AmenityController::class, 'update']);

    Route::middleware(['auth:sanctum', 'permission:amenities.delete'])
        ->delete('/{amenity}', [AmenityController::class, 'destroy']);
});

// Booking API-EndPoint
Route::prefix('v1/bookings')->group(function () {

    Route::middleware('auth:sanctum')
        ->get('/', [BookingController::class, 'index']);

    Route::get('/availability', [
        BookingController::class,
        'availability',
    ])->middleware('throttle:availability');

    Route::get('/calendar', [
        BookingController::class,
        'availabilityCalendar',
    ])->middleware('throttle:availability');

    Route::middleware([
        'auth:sanctum',
        'permission:bookings.create',
        'throttle:booking-create',
    ])->post('/', [BookingController::class, 'store']);

    Route::middleware([
        'auth:sanctum',
        'permission:bookings.confirm',
    ])->post('/{booking}/confirm', [BookingController::class, 'confirm']);

    Route::middleware([
        'auth:sanctum',
        'permission:bookings.checkin',
    ])->post('/{booking}/check-in', [BookingController::class, 'checkIn']);

    Route::middleware([
        'auth:sanctum',
        'permission:bookings.cancel',
    ])->post('/{booking}/cancel', [BookingController::class, 'cancel']);

    Route::middleware('auth:sanctum')
        ->post('/{booking}/request-cancellation', [
            BookingController::class,
            'requestCancellation',
        ]);

    Route::middleware([
        'auth:sanctum',
        'permission:bookings.cancel',
    ])->post('/{booking}/approve-cancellation', [
        BookingController::class,
        'approveCancellation',
    ]);

    Route::middleware([
        'auth:sanctum',
        'permission:bookings.cancel',
    ])->post('/{booking}/reject-cancellation', [
        BookingController::class,
        'rejectCancellation',
    ]);

    Route::middleware([
        'auth:sanctum',
        'permission:bookings.complete',
    ])->post('/{booking}/complete', [BookingController::class, 'complete']);

    Route::middleware(['auth:sanctum', 'permission:payments.view'])
        ->get('/{booking}/payments', [BookingPaymentController::class, 'index']);

    Route::middleware([
        'auth:sanctum',
        'permission:payments.create',
        'throttle:payment-create',
    ])->post('/{booking}/payments', [BookingPaymentController::class, 'store']);

    Route::middleware(['auth:sanctum', 'throttle:payment-create'])
        ->post('/{booking}/deposit-payment', [BookingPaymentController::class, 'deposit']);
});

// Payment API-EndPoint
Route::prefix('v1/payments')
    ->middleware('auth:sanctum')
    ->group(function () {

        Route::post('/', [PaymentController::class, 'store'])
            ->middleware([
                'permission:payments.create',
                'throttle:payment-create',
            ]);

        Route::post('/{payment}/paid', [PaymentController::class, 'paid'])
            ->middleware('permission:payments.update');

        Route::post('/{payment}/failed', [PaymentController::class, 'failed'])
            ->middleware('permission:payments.update');

        Route::post('/{payment}/refund', [PaymentController::class, 'refund'])
            ->middleware('permission:payments.refund');
    });

// Guest API-EndPoint
Route::prefix('v1/guests')
    ->middleware('auth:sanctum')
    ->group(function () {

        Route::middleware('permission:guests.view')
            ->get('/', [GuestController::class, 'index']);

        Route::middleware('permission:guests.view')
            ->get('/{guest}', [GuestController::class, 'show']);

        Route::middleware('permission:guests.create')
            ->post('/', [GuestController::class, 'store']);

        Route::middleware('permission:guests.update')
            ->put('/{guest}', [GuestController::class, 'update']);

        Route::middleware('permission:guests.delete')
            ->delete('/{guest}', [GuestController::class, 'destroy']);
    });

// Coupon API-EndPoint
Route::prefix('v1/coupons')->group(function () {

    Route::get('/active', [
        CouponController::class,
        'active',
    ])->middleware('throttle:public-api');

    Route::get('/validate/{code}', [
        CouponController::class,
        'validateCode',
    ])->middleware('throttle:public-api');

    Route::get('/{coupon}', [
        CouponController::class,
        'show',
    ])->middleware(['auth:sanctum', 'permission:coupons.view']);

    Route::middleware('auth:sanctum')
        ->group(function () {

            Route::middleware('permission:coupons.view')
                ->get('/', [CouponController::class, 'index']);

            Route::middleware('permission:coupons.create')
                ->post('/', [CouponController::class, 'store']);

            Route::middleware('permission:coupons.update')
                ->put('/{coupon}', [CouponController::class, 'update']);

            Route::middleware('permission:coupons.delete')
                ->delete('/{coupon}', [CouponController::class, 'destroy']);
        });
});

// Review API-EndPoint
Route::prefix('v1/reviews')->group(function () {

    Route::get('/approved', [
        ReviewController::class,
        'approved',
    ])->middleware('throttle:public-api');

    Route::middleware(['auth:sanctum', 'permission:reviews.view'])
        ->get('/', [ReviewController::class, 'index']);

    Route::get('/{review}', [ReviewController::class, 'show']);

    Route::middleware('auth:sanctum')
        ->post('/', [ReviewController::class, 'store']);

    Route::middleware('auth:sanctum')
        ->put('/{review}', [ReviewController::class, 'update']);

    Route::middleware('auth:sanctum')
        ->delete('/{review}', [ReviewController::class, 'destroy']);

    Route::middleware(['auth:sanctum', 'permission:reviews.approve'])
        ->post('/{review}/approve', [ReviewController::class, 'approve']);

    Route::middleware(['auth:sanctum', 'permission:reviews.reject'])
        ->post('/{review}/reject', [ReviewController::class, 'reject']);
});

// Staff API-EndPoint
Route::prefix('v1/staff')
    ->middleware('auth:sanctum')
    ->group(function () {

        Route::middleware('permission:staff.view')
            ->get('/', [StaffController::class, 'index']);

        Route::middleware('permission:staff.view')
            ->get('/{staff}', [StaffController::class, 'show']);

        Route::middleware('permission:staff.create')
            ->post('/', [StaffController::class, 'store']);

        Route::middleware('permission:staff.update')
            ->put('/{staff}', [StaffController::class, 'update']);

        Route::middleware('permission:staff.delete')
            ->delete('/{staff}', [StaffController::class, 'destroy']);

        Route::middleware('permission:staff.update')
            ->post('/{staff}/photo', [StaffController::class, 'uploadPhoto']);

        Route::middleware('permission:staff.update')
            ->delete('/{staff}/photo', [StaffController::class, 'removePhoto']);
    });

// Dashboard API-EndPoint
Route::prefix('v1/dashboard')
    ->middleware([
        'auth:sanctum',
        'permission:dashboard.view',
    ])
    ->group(function () {
        Route::get('/summary', [DashboardController::class, 'summary']);
    });

// Export API-EndPoint
Route::prefix('v1/exports')
    ->middleware([
        'auth:sanctum',
        'permission:dashboard.view',
    ])
    ->group(function () {
        Route::get('/bookings', [ExportController::class, 'bookings']);
        Route::get('/guests', [ExportController::class, 'guests']);
        Route::get('/payments', [ExportController::class, 'payments']);
    });
