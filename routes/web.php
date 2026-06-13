<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\OrderController;
use App\Http\Controllers\Shop\ProductController;
use App\Http\Controllers\Shop\TicketController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
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
Route::get('shop/{slug}', [ProductController::class, 'show'])->name('shop.show');

// Referral
Route::get('ref/{code}', [ReferralController::class, 'show'])->name('referral.show');

// Cart
Route::get('cart', [CartController::class, 'index'])->name('shop.cart');
Route::post('cart/add', [CartController::class, 'add'])->name('shop.cart.add');
Route::patch('cart/update', [CartController::class, 'update'])->name('shop.cart.update');
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
