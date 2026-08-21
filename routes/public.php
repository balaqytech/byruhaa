<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\PublicEventInterestController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\SitemapController;
use App\Http\Middleware\CaptureAffiliateReferral;
use App\Modules\Content\Http\Controllers\PublicPageController;
use Illuminate\Support\Facades\Route;

Route::get('sitemap.xml', SitemapController::class)->name('sitemap');

Route::middleware(CaptureAffiliateReferral::class)->group(function (): void {
    Route::get('/', [PublicSiteController::class, 'home'])->name('home');
    Route::get('coffee', [PublicSiteController::class, 'coffee'])->name('coffee');
    Route::get('events', [PublicSiteController::class, 'events'])->name('events.index');
    Route::get('events/{event:slug}', [PublicSiteController::class, 'event'])->name('events.show');
    Route::post('events/{event:slug}/interest', PublicEventInterestController::class)
        ->middleware(['auth:customer', 'throttle:10,1'])
        ->name('events.interests.store');
    Route::get('about', [PublicSiteController::class, 'about'])->name('about');
    Route::get('contact', [PublicSiteController::class, 'contact'])->name('contact');

    Route::get('policies/{page}', [PublicPageController::class, 'show'])->name('policies.show');

    Route::get('blog', [BlogController::class, 'index'])->name('blog.index');
    Route::get('blog/category/{category:slug}', [BlogController::class, 'category'])->name('blog.category');
    Route::get('blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');
});
