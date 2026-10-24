<?php

use App\Models\User;

test('the admin lands on the admin dashboard after logging in', function () {
    $admin = User::factory()->admin()->create();

    $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('admin.dashboard'));
});

test('customers land on their account after logging in', function () {
    $customer = User::factory()->create();

    $this->post(route('login.store'), ['email' => $customer->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard'));
});

test('logging in to book a flight goes back to the booking page', function () {
    $customer = User::factory()->create();

    $this->get(route('flights.book', 'off_123'));

    $this->post(route('login.store'), ['email' => $customer->email, 'password' => 'password'])
        ->assertRedirect(route('flights.book', 'off_123'));
});

test('the login page links back to the home page', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee(route('home'))
        ->assertSee('Home');
});
