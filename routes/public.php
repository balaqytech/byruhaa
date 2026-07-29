<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Middleware\CaptureAffiliateReferral;
use Illuminate\Support\Facades\Route;

Route::middleware(CaptureAffiliateReferral::class)->group(function (): void {
    Route::get('/', [PublicSiteController::class, 'home'])->name('home');
    Route::get('coffee', [PublicSiteController::class, 'coffee'])->name('coffee');
    Route::get('events', [PublicSiteController::class, 'events'])->name('events.index');
    Route::get('events/{event:slug}', [PublicSiteController::class, 'event'])->name('events.show');
    Route::get('about', [PublicSiteController::class, 'about'])->name('about');
    Route::get('contact', [PublicSiteController::class, 'contact'])->name('contact');

    Route::get('blog', [BlogController::class, 'index'])->name('blog.index');
    Route::get('blog/category/{category:slug}', [BlogController::class, 'category'])->name('blog.category');
    Route::get('blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');
});
