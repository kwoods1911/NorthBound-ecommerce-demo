<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the shared Collection component with its own copy', function () {
    Product::factory()->count(3)->create();

    $this->get('/new-arrivals')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Collection')
            ->where('collection.key', 'new-arrivals')
            ->where('collection.title', 'New Arrivals')
            ->has('products.data', 3)
            ->has('filterOptions.colors')
            ->has('filterOptions.categories')
        );
});

it('hides the category filter on the Accessories collection', function () {
    $accessories = Category::factory()->create(['slug' => 'travel-accessories']);
    Product::factory()->for($accessories)->create();
    Product::factory()->create(); // a non-accessory, should not appear

    $this->get('/accessories')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Collection')
            ->has('products.data', 1)
            ->missing('filterOptions.categories')
            ->where('filterOptions.showOnSaleFilter', true)
        );
});

it('only shows discounted products on Deals and offers no on-sale filter', function () {
    Product::factory()->create(['discount_price_cents' => 5000]);
    Product::factory()->create(['discount_price_cents' => null]);

    $this->get('/deals')
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('filterOptions.showOnSaleFilter', false)
        );
});

it('applies the price bucket filter from the query string', function () {
    Product::factory()->create(['price_cents' => 3000, 'discount_price_cents' => null]);
    Product::factory()->create(['price_cents' => 25000, 'discount_price_cents' => null]);

    $this->get('/new-arrivals?price=under-50')
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('filters.price', 'under-50')
        );
});

it('applies the colour filter from the query string', function () {
    $navy = Product::factory()->create();
    ProductVariant::factory()->for($navy)->create(['color' => 'Navy']);
    $sand = Product::factory()->create();
    ProductVariant::factory()->for($sand)->create(['color' => 'Sand']);

    $this->get('/best-sellers?colors[]=Navy')
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.slug', $navy->slug)
        );
});

it('supports Inertia partial reloads of just the products prop', function () {
    Product::factory()->count(2)->create();

    $version = (new \App\Http\Middleware\HandleInertiaRequests)->version(request());

    $response = $this->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (string) $version,
        'X-Inertia-Partial-Component' => 'Collection',
        'X-Inertia-Partial-Data' => 'products',
    ])->get('/new-arrivals');

    $response->assertOk();
    $props = $response->json('props');

    expect($props)->toHaveKey('products')
        ->and($props)->not->toHaveKey('filterOptions')
        ->and($props)->not->toHaveKey('collection');
});

it('rejects an invalid sort value', function () {
    $this->get('/new-arrivals?sort=bogus')->assertSessionHasErrors('sort');
});

it('paginates at the configured page size', function () {
    config()->set('shop.per_page', 12);
    Product::factory()->count(15)->create();

    $this->get('/new-arrivals?page=2')
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 3)
            ->where('products.meta.current_page', 2)
        );
});
