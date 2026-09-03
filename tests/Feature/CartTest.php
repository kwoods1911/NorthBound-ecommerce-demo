<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;

it('adds a product to a fresh guest cart', function () {
    $product = Product::factory()->create(['stock_quantity' => 10]);

    $this->post('/cart', ['product_id' => $product->id, 'quantity' => 2])
        ->assertRedirect();

    $cart = Cart::first();
    expect($cart->user_id)->toBeNull()
        ->and($cart->session_id)->not->toBeNull()
        ->and($cart->items()->first()->quantity)->toBe(2);
});

it('increments quantity when the same product+variant is added again', function () {
    $product = Product::factory()->create(['stock_quantity' => 10]);
    $variant = ProductVariant::factory()->for($product)->create();

    $this->post('/cart', ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 1]);
    $this->post('/cart', ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 2]);

    expect(CartItem::count())->toBe(1)
        ->and(CartItem::first()->quantity)->toBe(3);
});

it('dedupes a no-variant line despite the nullable unique index', function () {
    $product = Product::factory()->create(['stock_quantity' => 10]);

    $this->post('/cart', ['product_id' => $product->id, 'quantity' => 1]);
    $this->post('/cart', ['product_id' => $product->id, 'quantity' => 1]);

    expect(CartItem::count())->toBe(1)
        ->and(CartItem::first()->quantity)->toBe(2);
});

it('refuses to add more than the available stock', function () {
    $product = Product::factory()->create(['stock_quantity' => 3]);

    $this->from('/cart')
        ->post('/cart', ['product_id' => $product->id, 'quantity' => 5])
        ->assertRedirect('/cart')
        ->assertSessionHas('error');

    expect(CartItem::count())->toBe(0);
});

it('removes the line when quantity is set to zero', function () {
    $product = Product::factory()->create(['stock_quantity' => 10]);
    $this->post('/cart', ['product_id' => $product->id, 'quantity' => 2]);
    $item = CartItem::first();

    $this->patch("/cart/items/{$item->id}", ['quantity' => 0])->assertRedirect();

    expect(CartItem::find($item->id))->toBeNull();
});

it('forbids touching a cart item that is not yours', function () {
    $product = Product::factory()->create(['stock_quantity' => 10]);
    $otherCart = Cart::factory()->create();
    $item = CartItem::factory()->for($otherCart)->for($product)->create();

    $this->delete("/cart/items/{$item->id}")->assertForbidden();

    expect(CartItem::find($item->id))->not->toBeNull();
});

it('keeps a guest cart across requests and exposes it as a shared prop', function () {
    $product = Product::factory()->create(['stock_quantity' => 10, 'price_cents' => 5000]);
    $this->post('/cart', ['product_id' => $product->id, 'quantity' => 2]);

    $this->get('/cart')->assertInertia(fn ($page) => $page
        ->where('cart.count', 2)
        ->where('cart.subtotal.cents', 10000)
    );
});
