<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::livewire('flights', 'pages::flights.index')->name('flights.index');
Route::livewire('my-booking', 'pages::my-booking')->name('my-booking');

Route::middleware('signed')->group(function () {
    Route::get('my-booking/{booking}', [BookingController::class, 'lookup'])->name('my-booking.show');
    Route::get('my-booking/{booking}/e-ticket', [BookingController::class, 'lookupTicket'])->name('my-booking.ticket');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('flights/{offer}/book', 'pages::flights.book')->name('flights.book');
    Route::get('bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    Route::get('bookings/{booking}/e-ticket', [BookingController::class, 'ticket'])->name('bookings.ticket');

    Route::livewire('dashboard', 'pages::account.dashboard')->name('dashboard');
    Route::livewire('trips', 'pages::account.trips')->name('trips');
    Route::livewire('wallet', 'pages::account.wallet')->name('wallet');
});

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
