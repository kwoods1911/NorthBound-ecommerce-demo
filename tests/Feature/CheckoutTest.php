<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Payments\StripeGateway;
use Tests\Fakes\FakeStripeGateway;

beforeEach(function () {
    $this->fakeStripe = new FakeStripeGateway;
    $this->swap(StripeGateway::class, $this->fakeStripe);
    config(['shop.checkout' => [
        'currency' => 'usd',
        'flat_shipping_cents' => 1500,
        'free_shipping_threshold_cents' => 15000,
        'tax_rate' => 0.0,
    ]]);
});

it('redirects to the cart when checkout is opened empty', function () {
    $this->get('/checkout')->assertRedirect('/cart');
});

it('recomputes the total server-side and ignores the client', function () {
    $product = Product::factory()->create(['price_cents' => 8000, 'stock_quantity' => 5]);
    addToCart($product, 1);

    $this->postJson('/checkout', shippingPayload(['total_cents' => 1]))->assertOk();

    $order = Order::first();
    // 1 x $80 = $80 subtotal, under the $150 free-shipping threshold → +$15 shipping.
    expect($order->subtotal_cents->cents)->toBe(8000)
        ->and($order->shipping_cents->cents)->toBe(1500)
        ->and($order->total_cents->cents)->toBe(9500);
});

it('creates a pending order with snapshotted line items', function () {
    $product = Product::factory()->create([
        'name' => 'The Carry-On',
        'price_cents' => 22000,
        'cost_price_cents' => 9000,
        'stock_quantity' => 5,
    ]);
    $variant = ProductVariant::factory()->for($product)->create([
        'color' => 'Navy', 'size' => 'Carry-On', 'sku' => 'TGC-NAVY',
    ]);
    addToCart($product, 2, $variant);

    $this->postJson('/checkout', shippingPayload())
        ->assertOk()
        ->assertJsonStructure(['clientSecret', 'confirmationUrl']);

    $order = Order::with('items')->first();
    expect($order->status)->toBe(OrderStatus::Pending);

    $line = $order->items->first();
    expect($line->product_name)->toBe('The Carry-On')
        ->and($line->variant_label)->toBe('Navy / Carry-On')
        ->and($line->sku)->toBe('TGC-NAVY')
        ->and($line->unit_price_cents->cents)->toBe(22000)
        ->and($line->unit_cost_cents->cents)->toBe(9000)
        ->and($line->quantity)->toBe(2)
        ->and($line->line_total_cents->cents)->toBe(44000);
});

it('asks Stripe for a PaymentIntent matching the order total', function () {
    $product = Product::factory()->create(['price_cents' => 12000, 'stock_quantity' => 5]);
    addToCart($product, 1);

    $this->postJson('/checkout', shippingPayload())->assertOk();

    $order = Order::first();
    expect($this->fakeStripe->lastAmount())->toBe($order->total_cents->cents)
        ->and($order->stripe_payment_intent_id)->not->toBeNull();
});

it('generates a collision-proof order number from the primary key', function () {
    $product = Product::factory()->create(['price_cents' => 5000, 'stock_quantity' => 20]);

    addToCart($product, 1);
    $this->postJson('/checkout', shippingPayload())->assertOk();
    $first = Order::latest('id')->first();

    $first->delete(); // deleting must not let the number be reused

    addToCart($product, 1);
    $this->postJson('/checkout', shippingPayload())->assertOk();
    $second = Order::latest('id')->first();

    expect($second->order_number)
        ->not->toBe($first->order_number)
        ->toStartWith('TGC-');
});

it('does not touch stock at checkout time', function () {
    $product = Product::factory()->create(['price_cents' => 5000, 'stock_quantity' => 5]);
    addToCart($product, 2);

    $this->postJson('/checkout', shippingPayload())->assertOk();

    expect($product->fresh()->stock_quantity)->toBe(5);
});

it('rejects checkout when a line now exceeds stock', function () {
    $product = Product::factory()->create(['price_cents' => 5000, 'stock_quantity' => 5]);
    addToCart($product, 3);
    $product->update(['stock_quantity' => 1]);

    $this->postJson('/checkout', shippingPayload())->assertStatus(422);

    expect(Order::count())->toBe(0);
});
