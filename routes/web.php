<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\OrderController;
use App\Http\Controllers\Shop\ProductController;
use App\Http\Controllers\Shop\TicketController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Static information pages
Route::inertia('/about', 'about')->name('about');
Route::inertia('/terms', 'terms')->name('terms');
Route::inertia('/privacy', 'privacy')->name('privacy');
Route::inertia('/shipping', 'shipping')->name('shipping');
Route::inertia('/sample-packs', 'sample-packs')->name('shop.sample-packs');

// Contact
Route::get('/contact', [ContactController::class, 'create'])->name('contact.create');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

// Social authentication (stubbed — wire up Laravel Socialite to enable)
Route::get('auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
    ->whereIn('provider', ['google', 'facebook'])
    ->name('social.redirect');
Route::get('auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->whereIn('provider', ['google', 'facebook'])
    ->name('social.callback');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/orders', [DashboardController::class, 'orders'])->name('dashboard.orders');
    Route::get('dashboard/profile', [DashboardController::class, 'profile'])->name('dashboard.profile');
    Route::patch('dashboard/profile', [DashboardController::class, 'updateProfile'])->name('dashboard.profile.update');
});

// Sitemap
Route::get('sitemap.xml', function () {
    $urls = [
        ['loc' => url('/'), 'priority' => '1.0', 'changefreq' => 'daily'],
        ['loc' => route('login'), 'priority' => '0.6', 'changefreq' => 'monthly'],
        ['loc' => route('register'), 'priority' => '0.6', 'changefreq' => 'monthly'],
    ];

    $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

    foreach ($urls as $url) {
        $xml .= "  <url>\n";
        $xml .= "    <loc>{$url['loc']}</loc>\n";
        $xml .= "    <changefreq>{$url['changefreq']}</changefreq>\n";
        $xml .= "    <priority>{$url['priority']}</priority>\n";
        $xml .= "  </url>\n";
    }

    $xml .= '</urlset>';

    return response($xml)->header('Content-Type', 'application/xml');
})->name('sitemap');

// Blog
Route::get('blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

// Shop
Route::get('shop', [ProductController::class, 'index'])->name('shop.index');
Route::inertia('/business-cards', 'shop/business-cards')->name('shop.business-cards');

// Referral
Route::get('ref/{code}', [ReferralController::class, 'show'])->name('referral.show');

// Cart
Route::get('cart', [CartController::class, 'index'])->name('shop.cart');
Route::post('cart/add', [CartController::class, 'add'])->name('shop.cart.add');
Route::delete('cart/remove', [CartController::class, 'remove'])->name('shop.cart.remove');

// Checkout (requires auth)
Route::middleware(['auth'])->group(function () {
    Route::get('checkout', [CheckoutController::class, 'show'])->name('shop.checkout');
    Route::post('checkout', [CheckoutController::class, 'store'])->name('shop.checkout.store');
    Route::post('checkout/paypal/create', [CheckoutController::class, 'paypalCreate'])->name('shop.checkout.paypal.create');
    Route::post('checkout/paypal/capture', [CheckoutController::class, 'paypalCapture'])->name('shop.checkout.paypal.capture');

    // Orders
    Route::get('orders', [OrderController::class, 'index'])->name('shop.orders.index');
    Route::get('orders/{id}', [OrderController::class, 'show'])->name('shop.orders.show')->whereNumber('id');

    // Support tickets
    Route::get('tickets', [TicketController::class, 'index'])->name('shop.tickets.index');
    Route::get('tickets/create', [TicketController::class, 'create'])->name('shop.tickets.create');
    Route::post('tickets', [TicketController::class, 'store'])->name('shop.tickets.store');
    Route::get('tickets/{id}', [TicketController::class, 'show'])->name('shop.tickets.show')->whereNumber('id');
    Route::post('tickets/{id}/reply', [TicketController::class, 'reply'])->name('shop.tickets.reply')->whereNumber('id');
});

require __DIR__.'/settings.php';

// Product detail by slug — placed after all named routes so only truly
// unmatched single-segment paths (i.e. product slugs) reach it.
// The controller returns a 404 if no product matches the slug.
Route::get('{slug}', [ProductController::class, 'show'])->name('shop.show');

// Catch-all 404 — must be the last route registered. Runs inside the web
// middleware stack so Inertia + shared props are available.
Route::fallback(function () {
    return Inertia::render('errors/not-found')
        ->toResponse(request())
        ->setStatusCode(404);
});
