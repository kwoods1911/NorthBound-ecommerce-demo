<?php

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the product detail page with its variants', function () {
    $product = Product::factory()->create(['stock_quantity' => 8]);
    ProductVariant::factory()->for($product)->create(['color' => 'Navy', 'size' => 'One Size']);

    $this->get("/products/{$product->slug}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Product/Show')
            ->where('product.slug', $product->slug)
            ->where('product.inStock', true)
            ->has('product.variants', 1)
            ->where('product.variants.0.label', 'Navy / One Size')
        );
});

it('adds a chosen variant to the cart from the detail page', function () {
    $product = Product::factory()->create(['stock_quantity' => 8]);
    $variant = ProductVariant::factory()->for($product)->create();

    $this->post('/cart', [
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
    ])->assertRedirect();

    expect(CartItem::first())
        ->product_id->toBe($product->id)
        ->product_variant_id->toBe($variant->id);
});
