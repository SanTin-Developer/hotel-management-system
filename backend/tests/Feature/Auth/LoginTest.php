<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);

    Role::create([
        'name' => 'customer',
        'guard_name' => 'web',
    ]);

    $user = User::factory()->create([
        'name' => 'SanTin',
        'email' => 'customer@example.com',
        'password' => Hash::make('password123'),
        'status' => 'active',
    ]);

    $user->assignRole('customer');
});

it('logs in with valid credentials', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'customer@example.com',
        'password' => 'password123',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Login successful.')
        ->assertJsonPath('user.email', 'customer@example.com')
        ->assertJsonPath('user.roles.0.name', 'customer')
        ->assertJsonStructure([
            'message',
            'user',
            'token',
        ]);

    expect($response->json('token'))->not->toBeEmpty();

    $this->assertDatabaseCount('personal_access_tokens', 1);
});

it('rejects an email that does not exist', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'notfound@example.com',
        'password' => 'password123',
    ]);

    $response
        ->assertStatus(404)
        ->assertJsonPath(
            'message',
            'No account found with this email or phone. Please register first.'
        )
        ->assertJsonPath(
            'errors.email.0',
            'No account found with this email or phone.'
        );
});

it('rejects an incorrect password', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'customer@example.com',
        'password' => 'wrong-password',
    ]);

    $response
        ->assertStatus(401)
        ->assertJsonPath(
            'message',
            'Incorrect password. Please try again.'
        )
        ->assertJsonPath(
            'errors.password.0',
            'Incorrect password.'
        );
});

it('rejects an inactive account', function () {
    $user = User::where('email', 'customer@example.com')->firstOrFail();

    $user->update([
        'status' => 'inactive',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'customer@example.com',
        'password' => 'password123',
    ]);

    $response
        ->assertStatus(403)
        ->assertJsonPath(
            'message',
            'Your account is inactive. Please contact support.'
        )
        ->assertJsonPath(
            'errors.account.0',
            'Your account is inactive.'
        );
});

it('rejects login when email is missing', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'password' => 'password123',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.email.0',
            'Email or phone is required.'
        );
});

it('rejects login when password is missing', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'customer@example.com',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.password.0',
            'Password is required.'
        );
});

it('can access the authenticated user with the issued token', function () {
    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'email' => 'customer@example.com',
        'password' => 'password123',
    ]);

    $token = $loginResponse->json('token');

    $response = $this
        ->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/auth/me');

    $response
        ->assertOk()
        ->assertJsonPath(
            'message',
            'Authenticated user retrieved successfully.'
        )
        ->assertJsonPath(
            'user.email',
            'customer@example.com'
        )
        ->assertJsonPath(
            'user.roles.0.name',
            'customer'
        );
});

it('can logout and revoke the current token', function () {
    $this->withoutMiddleware(ThrottleRequests::class);

    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'email' => 'customer@example.com',
        'password' => 'password123',
    ]);

    $loginResponse->assertOk();

    $token = $loginResponse->json('token');

    expect(
        $this->app['db']
            ->table('personal_access_tokens')
            ->count()
    )->toBe(1);

    $logoutResponse = $this
        ->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/auth/logout');

    $logoutResponse
        ->assertOk()
        ->assertJsonPath(
            'message',
            'Logout successful.'
        );

    // The Sanctum token must be deleted.
    expect(
        $this->app['db']
            ->table('personal_access_tokens')
            ->count()
    )->toBe(0);
});

it('rate limits repeated login attempts', function () {
    $this->withMiddleware(ThrottleRequests::class);

    $email = 'attack@example.com';
    $ip = request()->ip() ?? '127.0.0.1';
    $key = md5('login'.$email.'|'.$ip);

    RateLimiter::clear($key);
    RateLimiter::clear($email.'|'.$ip);

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'wrong-password',
        ]);
    }

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $email,
        'password' => 'wrong-password',
    ]);
    $response->assertStatus(429);

    RateLimiter::clear($key);
    RateLimiter::clear($email.'|'.$ip);
});
