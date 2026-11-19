<?php

use App\Models\Product;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('redirects guests to login for every wishlist route', function () {
    $product = Product::factory()->create();

    $this->post('/wishlist', ['product_id' => $product->id])->assertRedirect('/login');
    $this->get('/account/wishlist')->assertRedirect('/login');
    $this->delete("/wishlist/{$product->id}")->assertRedirect('/login');
});

it('lets an authenticated user add a product to their wishlist', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($user)
        ->post('/wishlist', ['product_id' => $product->id])
        ->assertRedirect();

    expect($user->wishlistProducts)->toHaveCount(1)
        ->and($user->wishlistProducts->first()->is($product))->toBeTrue();
});

it('does not duplicate when the same product is added twice', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($user)->post('/wishlist', ['product_id' => $product->id]);
    $this->actingAs($user)->post('/wishlist', ['product_id' => $product->id]);

    expect($user->wishlistProducts()->count())->toBe(1);
});

it('rejects a non-existent product id', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/wishlist', ['product_id' => 999999])
        ->assertInvalid(['product_id']);
});

it('lets an authenticated user remove a product from their wishlist', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $user->wishlistProducts()->attach($product, ['created_at' => now()]);

    $this->actingAs($user)
        ->delete("/wishlist/{$product->id}")
        ->assertRedirect();

    expect($user->wishlistProducts()->count())->toBe(0);
});

it('is a no-op to remove a product that was never wishlisted', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($user)
        ->delete("/wishlist/{$product->id}")
        ->assertRedirect();

    expect($user->wishlistProducts()->count())->toBe(0);
});

it('renders the account wishlist page with saved products', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['name' => 'Saved Carry-On']);
    $user->wishlistProducts()->attach($product, ['created_at' => now()]);

    $this->actingAs($user)
        ->get('/account/wishlist')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('account/Wishlist')
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Saved Carry-On')
        );
});

it('exposes wishlist state on the product detail page', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($user)
        ->get("/products/{$product->slug}")
        ->assertInertia(fn (Assert $page) => $page->where('isWishlisted', false));

    $user->wishlistProducts()->attach($product, ['created_at' => now()]);

    $this->actingAs($user)
        ->get("/products/{$product->slug}")
        ->assertInertia(fn (Assert $page) => $page->where('isWishlisted', true));
});

it('shows isWishlisted as false for guests', function () {
    $product = Product::factory()->create();

    $this->get("/products/{$product->slug}")
        ->assertInertia(fn (Assert $page) => $page->where('isWishlisted', false));
});
