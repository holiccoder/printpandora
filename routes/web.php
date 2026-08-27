<?php

use App\Http\Controllers\AdminAiChatController;
use App\Http\Controllers\AiChatController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DesignServiceRequestController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\ShowcaseController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\OrderController;
use App\Http\Controllers\Shop\ProductController;
use App\Http\Controllers\Shop\TicketController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Static information pages
Route::inertia('/about-inkpavo', 'about')->name('about');
Route::inertia('/terms-and-conditions', 'terms')->name('terms');
Route::inertia('/privacy-policy', 'privacy')->name('privacy');
Route::inertia('/affiliate-program', 'affiliate-program')->name('affiliate-program');
Route::inertia('/affiliate-program-terms-and-conditions', 'affiliate-terms')->name('affiliate-terms');
Route::inertia('/shipping-policy', 'shipping')->name('shipping');
Route::inertia('/shipping-and-cost-calculator', 'shipping-calculator')->name('shipping.calculator');
Route::get('/faq-and-help-center', [HelpController::class, 'index'])->name('help');
Route::get('/faq-and-help-center/categories/{slug}', [HelpController::class, 'category'])->name('help.category');
Route::get('/faq-and-help-center/articles/{slug}', [HelpController::class, 'article'])->name('help.article');
Route::inertia('/sample-packs', 'sample-packs')->name('shop.sample-packs');
Route::inertia('/business-card-sample-pack', 'business-card-sample-pack')->name('shop.business-card-sample-pack');
Route::inertia('/free-sample-pack', 'free-sample-pack')->name('shop.free-sample-pack');
Route::get('/business-card-design-service', [DesignServiceRequestController::class, 'create'])->name('business-card-design-service');
Route::post('/business-card-design-service', [DesignServiceRequestController::class, 'store'])->name('business-card-design-service.store');
Route::inertia('/postcards', 'postcards')->name('postcards');
Route::inertia('/stickers-and-labels', 'stickers-and-labels')->name('stickers-and-labels');
Route::inertia('/flyers-and-brochures', 'flyers-and-brochures')->name('flyers-and-brochures');
Route::get('/showcases', [ShowcaseController::class, 'index'])->name('showcases');

// Contact
Route::get('/contact-us', [ContactController::class, 'create'])->name('contact.create');
Route::post('/contact-us', [ContactController::class, 'store'])->name('contact.store');

// Social authentication
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

// Product catalog
Route::inertia('/business-cards', 'shop/business-cards')->name('shop.business-cards');
Route::get('business-cards/{slug}', [ProductController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('shop.business-card.show');

// Referral
Route::get('ref/{code}', [ReferralController::class, 'show'])->name('referral.show');

// Cart
Route::get('cart', [CartController::class, 'index'])->name('shop.cart');
Route::post('cart/add', [CartController::class, 'add'])->name('shop.cart.add');
Route::delete('cart/remove', [CartController::class, 'remove'])->name('shop.cart.remove');
Route::post('cart/discount', [CartController::class, 'applyDiscount'])->name('shop.cart.discount.apply');
Route::delete('cart/discount', [CartController::class, 'removeDiscount'])->name('shop.cart.discount.remove');

// Checkout (requires auth)
Route::middleware(['auth'])->group(function () {
    Route::get('checkout', [CheckoutController::class, 'show'])->name('shop.checkout');
    Route::post('checkout', [CheckoutController::class, 'store'])->name('shop.checkout.store');
    Route::post('checkout/paypal/create', [CheckoutController::class, 'paypalCreate'])->name('shop.checkout.paypal.create');
    Route::post('checkout/paypal/capture', [CheckoutController::class, 'paypalCapture'])->name('shop.checkout.paypal.capture');
    Route::post('checkout/cryptomus/create', [CheckoutController::class, 'cryptomusCreate'])->name('shop.checkout.cryptomus.create');

    // Orders
    Route::get('thank-you/{id}', [OrderController::class, 'thankYou'])
        ->whereNumber('id')
        ->name('shop.checkout.thank-you');
    Route::get('orders', [OrderController::class, 'index'])->name('shop.orders.index');
    Route::get('orders/{id}', [OrderController::class, 'show'])->name('shop.orders.show')->whereNumber('id');

    // Support tickets
    Route::get('tickets', [TicketController::class, 'index'])->name('shop.tickets.index');
    Route::post('tickets', [TicketController::class, 'store'])->name('shop.tickets.store');
    Route::get('tickets/{id}', [TicketController::class, 'show'])->name('shop.tickets.show')->whereNumber('id');
    Route::post('tickets/{id}/reply', [TicketController::class, 'reply'])->name('shop.tickets.reply')->whereNumber('id');
});

// PayPal webhook (public — called by PayPal servers).
Route::post('checkout/paypal/webhook', [CheckoutController::class, 'paypalWebhook'])
    ->name('shop.checkout.paypal.webhook');

// Cryptomus webhook (public — called by Cryptomus servers)
Route::post('checkout/cryptomus/webhook', [CheckoutController::class, 'cryptomusWebhook'])
    ->name('shop.checkout.cryptomus.webhook');

require __DIR__.'/settings.php';

// AI support chat (public, rate-limited)
Route::post('ai/chat', [AiChatController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('ai.chat');
Route::post('ai/chat/handoff', [AiChatController::class, 'handoff'])
    ->middleware('throttle:20,1')
    ->name('ai.chat.handoff');
Route::post('ai/chat/message', [AiChatController::class, 'message'])
    ->middleware('throttle:30,1')
    ->name('ai.chat.message');
Route::get('ai/chat/poll', [AiChatController::class, 'poll'])
    ->middleware('throttle:120,1')
    ->name('ai.chat.poll');

// AI support chat — admin bubble endpoints (Filament, admin guard)
Route::middleware(['auth:admin'])->prefix('ai/chat/admin')->name('ai.chat.admin.')->group(function () {
    Route::get('conversations', [AdminAiChatController::class, 'index'])->name('conversations');
    Route::get('conversations/{conversation}/messages', [AdminAiChatController::class, 'messages'])->name('messages');
    Route::post('conversations/{conversation}/reply', [AdminAiChatController::class, 'reply'])->name('reply');
    Route::post('conversations/{conversation}/resolve', [AdminAiChatController::class, 'resolve'])->name('resolve');
    Route::post('conversations/{conversation}/takeover', [AdminAiChatController::class, 'takeover'])->name('takeover');
});

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
