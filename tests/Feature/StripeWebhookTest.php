<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Services\Payments\StripeGateway;
use Tests\Fakes\FakeStripeGateway;

beforeEach(function () {
    $this->swap(StripeGateway::class, new FakeStripeGateway);
    config([
        'services.stripe.webhook_secret' => 'whsec_test',
        'shop.checkout' => [
            'currency' => 'usd', 'flat_shipping_cents' => 1500,
            'free_shipping_threshold_cents' => 15000, 'tax_rate' => 0.0,
        ],
    ]);
});

function placePendingOrder(int $stock = 5, int $qty = 2): Order
{
    $product = Product::factory()->create(['price_cents' => 5000, 'stock_quantity' => $stock]);
    addToCart($product, $qty);
    test()->postJson('/checkout', shippingPayload())->assertOk();

    return Order::with('items')->firstOrFail();
}

function fireWebhook(Order $order, string $type = 'payment_intent.succeeded')
{
    $payload = json_encode([
        'id' => 'evt_'.uniqid(),
        'type' => $type,
        'data' => ['object' => [
            'id' => $order->stripe_payment_intent_id,
            'object' => 'payment_intent',
            'status' => 'succeeded',
            'metadata' => ['order_id' => $order->id, 'order_number' => $order->order_number],
        ]],
    ]);

    return test()->call('POST', '/stripe/webhook', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => stripeSignature($payload, 'whsec_test'),
        'CONTENT_TYPE' => 'application/json',
    ], $payload);
}

it('marks the order paid and draws down stock on payment_intent.succeeded', function () {
    $order = placePendingOrder(stock: 5, qty: 2);
    $product = $order->items->first()->product;
    $startSales = $product->sales_count;

    fireWebhook($order)->assertOk();

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->paid_at)->not->toBeNull()
        ->and($product->fresh()->stock_quantity)->toBe(3)
        ->and($product->fresh()->sales_count)->toBe($startSales + 2)
        ->and($order->cart->items()->count())->toBe(0);
});

it('is idempotent when the same event is delivered twice', function () {
    $order = placePendingOrder(stock: 10, qty: 3);
    $product = $order->items->first()->product;
    $startSales = $product->sales_count;

    fireWebhook($order)->assertOk();
    fireWebhook($order)->assertOk();

    expect($product->fresh()->stock_quantity)->toBe(7)
        ->and($product->fresh()->sales_count)->toBe($startSales + 3);
});

it('rejects a payload with a bad signature', function () {
    $order = placePendingOrder();

    $this->call('POST', '/stripe/webhook', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => 't=1,v1=deadbeef',
        'CONTENT_TYPE' => 'application/json',
    ], '{"type":"payment_intent.succeeded"}')->assertStatus(400);

    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});

it('ignores an event for an unknown order', function () {
    $payload = json_encode([
        'id' => 'evt_x', 'type' => 'payment_intent.succeeded',
        'data' => ['object' => ['id' => 'pi_missing', 'object' => 'payment_intent', 'metadata' => []]],
    ]);

    $this->call('POST', '/stripe/webhook', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => stripeSignature($payload, 'whsec_test'),
        'CONTENT_TYPE' => 'application/json',
    ], $payload)->assertOk();
});
