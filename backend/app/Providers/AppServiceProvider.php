<?php

namespace App\Providers;

use App\Mail\Transport\BrevoApiTransport;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Guest;
use App\Models\Review;
use App\Models\Room;
use App\Models\Staff;
use App\Models\User;
use App\Policies\BookingPolicy;
use App\Policies\CouponPolicy;
use App\Policies\GuestPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\RoomPolicy;
use App\Policies\StaffPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Mail::extend('brevo', function (array $config) {
            return new BrevoApiTransport((string) config('services.brevo.key'));
        });

        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(Guest::class, GuestPolicy::class);
        Gate::policy(Coupon::class, CouponPolicy::class);
        Gate::policy(Review::class, ReviewPolicy::class);
        Gate::policy(Staff::class, StaffPolicy::class);
        Gate::policy(Room::class, RoomPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)
                ->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('otp', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip());
        });

        RateLimiter::for('booking-create', function (Request $request) {
            return Limit::perMinute(10)
                ->by(
                    $request->user()?->id
                    ?? $request->ip()
                );
        });

        RateLimiter::for('payment-create', function (Request $request) {
            return Limit::perMinute(10)
                ->by(
                    $request->user()?->id
                    ?? $request->ip()
                );
        });

        RateLimiter::for('availability', function (Request $request) {
            return Limit::perMinute(30)
                ->by($request->ip());
        });

        RateLimiter::for('public-api', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->ip());
        });
    }
}
