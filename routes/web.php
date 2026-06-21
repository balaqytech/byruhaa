<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\ThawaniPaymentReturnController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('blog/category/{category:slug}', [BlogController::class, 'category'])->name('blog.category');
Route::get('blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');

Route::middleware('signed')->group(function () {
    Route::get('payments/thawani/{payment}/success', [ThawaniPaymentReturnController::class, 'success'])->name('payments.thawani.success');
    Route::get('payments/thawani/{payment}/cancel', [ThawaniPaymentReturnController::class, 'cancel'])->name('payments.thawani.cancel');
});

Route::middleware(['auth:customer'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('events', 'pages::events.index')->name('events.index');
    Route::livewire('events/{event:slug}', 'pages::events.show')->name('events.show');
    Route::livewire('family-members', 'pages::family-members.index')->name('family-members.index');
    Route::livewire('bookings', 'pages::bookings.index')->name('bookings.index');
    Route::livewire('bookings/{booking}/contracts/{contract}', 'pages::bookings.contract')->name('bookings.contracts.show');
    Route::livewire('bookings/{booking}', 'pages::bookings.show')->name('bookings.show');
    Route::livewire('payments', 'pages::payments.index')->name('payments.index');
});

require __DIR__.'/settings.php';
