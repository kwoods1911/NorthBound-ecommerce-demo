<?php

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;

/*
 * MergeGuestCartOnLogin is registered on Illuminate\Auth\Events\Login in
 * AppServiceProvider. These tests prove it fires for the real Breeze
 * register/login routes — not just when the event is dispatched by hand
 * (see tests/Feature/CartMergeTest.php for the unit-level coverage).
 */

it('merges the guest cart into the user cart on login', function () {
    $product = Product::factory()->create(['stock_quantity' => 10]);
    $user = User::factory()->create();

    $this->post('/cart', ['product_id' => $product->id, 'quantity' => 2]);
    expect(session('cart_token'))->not->toBeNull();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('account', absolute: false));

    $user->refresh();
    expect($user->cart)->not->toBeNull()
        ->and($user->cart->items()->where('product_id', $product->id)->value('quantity'))->toBe(2)
        ->and(session('cart_token'))->toBeNull()
        ->and(Cart::whereNull('user_id')->count())->toBe(0);
});

it('carries the guest cart into a brand-new account on registration', function () {
    $product = Product::factory()->create(['stock_quantity' => 10]);

    $this->post('/cart', ['product_id' => $product->id, 'quantity' => 3]);

    $this->post('/register', [
        'name' => 'New Shopper',
        'email' => 'shopper@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('account', absolute: false));

    $user = User::firstWhere('email', 'shopper@example.test');
    expect($user->cart->items()->where('product_id', $product->id)->value('quantity'))->toBe(3)
        ->and(session('cart_token'))->toBeNull();
});
