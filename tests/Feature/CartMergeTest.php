<?php

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;

it('merges the guest cart into the user cart on login', function () {
    $product = Product::factory()->create(['stock_quantity' => 10]);
    $user = User::factory()->create();

    // Guest adds two.
    $this->post('/cart', ['product_id' => $product->id, 'quantity' => 2]);

    // User already had one of the same product waiting.
    $userCart = $user->cart()->create([]);
    $userCart->items()->create(['product_id' => $product->id, 'quantity' => 1]);

    $this->actingAs($user);
    event(new Illuminate\Auth\Events\Login('web', $user, false));

    expect($user->fresh()->cart->items()->count())->toBe(1)
        ->and($user->fresh()->cart->items()->first()->quantity)->toBe(3)
        ->and(session('cart_token'))->toBeNull()
        ->and(Cart::count())->toBe(1);
});

it('clamps merged quantity to available stock', function () {
    $product = Product::factory()->create(['stock_quantity' => 4]);
    $user = User::factory()->create();

    $this->post('/cart', ['product_id' => $product->id, 'quantity' => 3]);
    $userCart = $user->cart()->create([]);
    $userCart->items()->create(['product_id' => $product->id, 'quantity' => 3]);

    $this->actingAs($user);
    event(new Illuminate\Auth\Events\Login('web', $user, false));

    expect($user->fresh()->cart->items()->first()->quantity)->toBe(4);
});
