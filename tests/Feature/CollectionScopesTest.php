<?php

use App\Enums\ProductSort;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;

it('orders new arrivals by newest first', function () {
    $old = Product::factory()->create(['created_at' => now()->subDays(10)]);
    $new = Product::factory()->create(['created_at' => now()->subDay()]);

    expect(Product::newArrivals()->pluck('id')->all())
        ->toBe([$new->id, $old->id]);
});

it('orders best sellers by sales_count desc', function () {
    $low = Product::factory()->create(['sales_count' => 3]);
    $high = Product::factory()->create(['sales_count' => 99]);

    expect(Product::bestSellers()->pluck('id')->all())
        ->toBe([$high->id, $low->id]);
});

it('filters to a single category by slug', function () {
    $accessories = Category::factory()->create(['slug' => 'travel-accessories']);
    $suitcases = Category::factory()->create(['slug' => 'suitcases']);

    $tag = Product::factory()->for($accessories)->create();
    Product::factory()->for($suitcases)->create();

    expect(Product::inCategory('travel-accessories')->pluck('id')->all())
        ->toBe([$tag->id]);
});

it('discounted returns only marked-down products', function () {
    $full = Product::factory()->create(['price_cents' => 20000, 'discount_price_cents' => null]);
    $sale = Product::factory()->create(['price_cents' => 20000, 'discount_price_cents' => 15000]);

    expect(Product::discounted()->pluck('id')->all())->toBe([$sale->id]);
});

it('price bucket filters on the effective (discounted) price', function () {
    // $220 list, marked down to $40 — belongs in the "under $50" bucket.
    $deepDiscount = Product::factory()->create([
        'price_cents' => 22000,
        'discount_price_cents' => 4000,
    ]);
    $midRange = Product::factory()->create([
        'price_cents' => 12000,
        'discount_price_cents' => null,
    ]);

    $under50 = Product::priceBucket(null, 4999)->pluck('id')->all();

    expect($under50)->toContain($deepDiscount->id)
        ->not->toContain($midRange->id);
});

it('filters by variant colour', function () {
    $navy = Product::factory()->create();
    ProductVariant::factory()->for($navy)->create(['color' => 'Navy']);

    $sand = Product::factory()->create();
    ProductVariant::factory()->for($sand)->create(['color' => 'Sand']);

    expect(Product::havingColors(['Navy'])->pluck('id')->all())->toBe([$navy->id]);
    expect(Product::havingColors([])->count())->toBe(2);
});

it('sorts by effective price low to high and high to low', function () {
    $cheap = Product::factory()->create(['price_cents' => 5000, 'discount_price_cents' => null]);
    $middle = Product::factory()->create(['price_cents' => 30000, 'discount_price_cents' => 9000]);
    $dear = Product::factory()->create(['price_cents' => 25000, 'discount_price_cents' => null]);

    expect(Product::query()->applySort(ProductSort::PriceLowHigh)->pluck('id')->all())
        ->toBe([$cheap->id, $middle->id, $dear->id]);

    expect(Product::query()->applySort(ProductSort::PriceHighLow)->pluck('id')->all())
        ->toBe([$dear->id, $middle->id, $cheap->id]);
});
