<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home', [
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');

Route::get('/new-arrivals', [CollectionController::class, 'newArrivals'])->name('collections.new-arrivals');
Route::get('/best-sellers', [CollectionController::class, 'bestSellers'])->name('collections.best-sellers');
Route::get('/accessories', [CollectionController::class, 'accessories'])->name('collections.accessories');
Route::get('/deals', [CollectionController::class, 'deals'])->name('collections.deals');

Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
Route::patch('/cart/items/{item}', [CartController::class, 'update'])->name('cart.items.update');
Route::delete('/cart/items/{item}', [CartController::class, 'destroy'])->name('cart.items.destroy');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/confirmation/{order:order_number}', [CheckoutController::class, 'confirmation'])
    ->name('checkout.confirmation');

Route::get('/chat/history', [ChatController::class, 'history'])->name('chat.history');
Route::post('/chat', [ChatController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('chat.store');

Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

Route::get('/account', [AccountController::class, 'show'])
    ->middleware('auth')
    ->name('account');

require __DIR__.'/auth.php';
