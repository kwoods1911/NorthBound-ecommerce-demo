<?php

use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
});

it('stores prices entered in dollars as integer cents', function () {
    $category = Category::factory()->create();

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Aluminium Carry-On',
            'slug' => 'aluminium-carry-on',
            'category_id' => $category->id,
            'description' => 'A sturdy carry-on.',
            'price_cents' => '295.00',
            'cost_price_cents' => '120.50',
            'discount_price_cents' => '249.00',
            'stock_quantity' => 12,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::firstWhere('slug', 'aluminium-carry-on');

    expect($product->price_cents->cents)->toBe(29_500)
        ->and($product->cost_price_cents->cents)->toBe(12_050)
        ->and($product->discount_price_cents->cents)->toBe(24_900)
        ->and($product->stock_quantity)->toBe(12);
});

it('round-trips cents back to dollars when editing', function () {
    $product = Product::factory()->create(['price_cents' => 18_000]);

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->assertFormSet(['price_cents' => '180.00']);
});

it('rejects a discount price above the price', function () {
    $category = Category::factory()->create();

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Bad Deal',
            'slug' => 'bad-deal',
            'category_id' => $category->id,
            'description' => 'x',
            'price_cents' => '100.00',
            'cost_price_cents' => '40.00',
            'discount_price_cents' => '150.00',
            'stock_quantity' => 1,
        ])
        ->call('create')
        ->assertHasFormErrors(['discount_price_cents']);
});
