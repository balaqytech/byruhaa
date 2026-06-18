<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth:customer'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('events', 'pages::events.index')->name('events.index');
    Route::livewire('events/{event:slug}', 'pages::events.show')->name('events.show');
    Route::livewire('family-members', 'pages::family-members.index')->name('family-members.index');
    Route::livewire('bookings', 'pages::bookings.index')->name('bookings.index');
    Route::livewire('bookings/{booking}', 'pages::bookings.show')->name('bookings.show');
});

require __DIR__.'/settings.php';
