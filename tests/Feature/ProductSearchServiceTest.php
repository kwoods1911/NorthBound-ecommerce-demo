<?php

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductSearchService;

beforeEach(fn () => $this->search = new ProductSearchService);

it('matches on name and description', function () {
    $byName = Product::factory()->create(['name' => 'Cabin Carry-On', 'description' => 'Hard shell.']);
    $byDescription = Product::factory()->create(['name' => 'The Globe', 'description' => 'A roomy carry-on for weekends.']);
    Product::factory()->create(['name' => 'Duffel', 'description' => 'Big bag.']);

    expect($this->search->search('carry-on')->pluck('id')->sort()->values()->all())
        ->toBe(collect([$byName->id, $byDescription->id])->sort()->values()->all());
});

it('narrows by category name or slug', function () {
    $bags = Category::factory()->create(['name' => 'Weekender Bags', 'slug' => 'weekender-bags']);
    $cases = Category::factory()->create(['name' => 'Suitcases', 'slug' => 'suitcases']);

    $bag = Product::factory()->for($bags)->create(['name' => 'Trip Bag']);
    Product::factory()->for($cases)->create(['name' => 'Trip Case']);

    expect($this->search->search('trip', 'weekender-bags')->pluck('id')->all())->toBe([$bag->id]);
    expect($this->search->search('trip', 'Weekender')->pluck('id')->all())->toBe([$bag->id]);
});

it('applies a max price against the discounted price', function () {
    $cheap = Product::factory()->create(['name' => 'Tag', 'price_cents' => 3000, 'discount_price_cents' => null]);
    $discountedUnder = Product::factory()->create(['name' => 'Tag Deluxe', 'price_cents' => 9000, 'discount_price_cents' => 4000]);
    Product::factory()->create(['name' => 'Tag Premium', 'price_cents' => 12000, 'discount_price_cents' => null]);

    expect($this->search->search('tag', maxPriceCents: 5000)->pluck('id')->sort()->values()->all())
        ->toBe(collect([$cheap->id, $discountedUnder->id])->sort()->values()->all());
});

it('caps the number of results', function () {
    Product::factory()->count(10)->create(['name' => 'Packing Cube']);

    expect($this->search->search('packing')->count())->toBe(ProductSearchService::DEFAULT_LIMIT);
});

it('returns stock details for a product and null for a missing one', function () {
    $product = Product::factory()->create(['stock_quantity' => 0]);

    expect($this->search->stockFor($product->id))->toMatchArray([
        'name' => $product->name,
        'stock_quantity' => 0,
        'in_stock' => false,
    ]);
    expect($this->search->stockFor(999999))->toBeNull();
});
