<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('home', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('locked users with status 0 cannot authenticate using the login screen', function () {
    $lockedUser = User::factory()->create([
        'status' => 0,
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post('/login', [
        'email' => $lockedUser->email,
        'password' => 'password123',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('email');
});

test('authenticated user whose status is changed to 0 is immediately logged out by middleware', function () {
    $user = User::factory()->create([
        'status' => 0,
    ]);

    $response = $this->actingAs($user)->get('/');

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('google login callback redirects to home and clears url.intended', function () {
    $abstractUser = Mockery::mock(Laravel\Socialite\Two\User::class);
    $abstractUser->shouldReceive('getId')->andReturn('google-id-12345');
    $abstractUser->shouldReceive('getName')->andReturn('Test Customer');
    $abstractUser->shouldReceive('getEmail')->andReturn('testcustomer@example.com');
    $abstractUser->shouldReceive('getAvatar')->andReturn('https://avatar.com/test.jpg');

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->andReturn($abstractUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    session(['url.intended' => 'http://localhost/dashboard']);

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('home'));
    $this->assertAuthenticated();
    expect(session('url.intended'))->toBeNull();
});

test('google login for admin still redirects to home page', function () {
    $admin = User::factory()->create([
        'email' => 'admin.test@freshfood.vn',
        'role' => 'admin',
        'status' => 1,
    ]);

    $abstractUser = Mockery::mock(Laravel\Socialite\Two\User::class);
    $abstractUser->shouldReceive('getId')->andReturn('google-admin-12345');
    $abstractUser->shouldReceive('getName')->andReturn('Admin FreshFood');
    $abstractUser->shouldReceive('getEmail')->andReturn('admin.test@freshfood.vn');
    $abstractUser->shouldReceive('getAvatar')->andReturn(null);

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->andReturn($abstractUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    session(['url.intended' => 'http://localhost/dashboard']);

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($admin);
});
