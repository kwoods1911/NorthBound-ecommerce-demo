<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Services\Payments\StripeGateway;
use Illuminate\Support\Facades\URL;
use Tests\Fakes\FakeStripeGateway;

beforeEach(function () {
    $this->fakeStripe = new FakeStripeGateway;
    $this->swap(StripeGateway::class, $this->fakeStripe);
    config(['shop.checkout' => [
        'currency' => 'usd', 'flat_shipping_cents' => 1500,
        'free_shipping_threshold_cents' => 15000, 'tax_rate' => 0.0,
    ]]);
});

function pendingOrder(): Order
{
    $product = Product::factory()->create(['price_cents' => 5000, 'stock_quantity' => 5]);
    addToCart($product, 1);
    test()->postJson('/checkout', shippingPayload())->assertOk();

    return Order::firstOrFail();
}

it('rejects an unsigned confirmation URL', function () {
    $order = pendingOrder();

    $this->get("/checkout/confirmation/{$order->order_number}")->assertForbidden();
});

it('renders the order on a valid signed URL', function () {
    $order = pendingOrder();

    $this->get(URL::signedRoute('checkout.confirmation', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Checkout/Confirmation')
            ->where('order.orderNumber', $order->order_number)
        );
});

it('finalises a still-pending order via the return fallback', function () {
    $order = pendingOrder();
    $this->fakeStripe->retrieveStatus = 'succeeded';

    $url = URL::signedRoute('checkout.confirmation', $order).'&payment_intent='.$order->stripe_payment_intent_id;

    $this->get($url)->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});

it('leaves the order pending when Stripe still reports it unpaid', function () {
    $order = pendingOrder();
    $this->fakeStripe->retrieveStatus = 'processing';

    $url = URL::signedRoute('checkout.confirmation', $order).'&payment_intent='.$order->stripe_payment_intent_id;

    $this->get($url)->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});
