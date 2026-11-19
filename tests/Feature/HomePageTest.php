<?php

use App\Models\HeroImage;
use App\Models\Product;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the homepage with hero, tiles and featured products', function () {
    HeroImage::factory()->active()->create(['headline' => 'Live hero']);
    Product::factory()->count(3)->create();

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('hero.headline', 'Live hero')
            ->has('collectionTiles', 4)
            ->has('featuredProducts.data')
            ->has('support.warranty')
        );
});

it('feeds the featured grid from best sellers, capped at the configured count', function () {
    config()->set('shop.featured_count', 2);

    Product::factory()->create(['name' => 'Low', 'sales_count' => 1]);
    Product::factory()->create(['name' => 'High', 'sales_count' => 999]);
    Product::factory()->create(['name' => 'Mid', 'sales_count' => 500]);

    $this->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->has('featuredProducts.data', 2)
            ->where('featuredProducts.data.0.name', 'High')
            ->where('featuredProducts.data.1.name', 'Mid')
        );
});

it('renders the about page', function () {
    $this->get('/about')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('About'));
});
