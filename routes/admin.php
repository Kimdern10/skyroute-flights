<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('/', 'pages::admin.dashboard')->name('dashboard');
    Route::livewire('bookings', 'pages::admin.bookings.index')->name('bookings.index');
    Route::livewire('bookings/{booking}', 'pages::admin.bookings.show')->name('bookings.show');
    Route::livewire('flights', 'pages::admin.flights')->name('flights');
    Route::livewire('top-ups', 'pages::admin.top-ups')->name('top-ups');
    Route::livewire('customers', 'pages::admin.customers')->name('customers');
    Route::livewire('settings', 'pages::admin.settings')->name('settings');
});
