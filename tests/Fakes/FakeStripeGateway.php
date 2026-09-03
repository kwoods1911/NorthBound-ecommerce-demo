<?php

namespace Tests\Fakes;

use App\Models\Order;
use App\Services\Payments\StripeGateway;
use Stripe\PaymentIntent;

/**
 * Stand-in for {@see StripeGateway} in tests. PaymentIntent create/retrieve are
 * faked; webhook signature construction is inherited from the real gateway so
 * signature verification is exercised for real.
 */
class FakeStripeGateway extends StripeGateway
{
    /** @var array<int, array{order: Order, options: array<string, mixed>}> */
    public array $intents = [];

    public string $retrieveStatus = 'succeeded';

    public function __construct()
    {
        // Intentionally skip parent::__construct — no StripeClient needed.
    }

    public function createPaymentIntent(Order $order): PaymentIntent
    {
        $this->intents[] = [
            'order' => $order,
            'options' => ['idempotency_key' => "pi_{$order->order_number}"],
        ];

        return PaymentIntent::constructFrom([
            'id' => 'pi_test_'.$order->id,
            'client_secret' => 'pi_test_'.$order->id.'_secret',
            'status' => 'requires_payment_method',
            'amount' => $order->total_cents->cents,
            'currency' => config('shop.checkout.currency'),
        ]);
    }

    public function retrievePaymentIntent(string $id): PaymentIntent
    {
        return PaymentIntent::constructFrom(['id' => $id, 'status' => $this->retrieveStatus]);
    }

    /** The most recent PaymentIntent amount requested. */
    public function lastAmount(): ?int
    {
        $last = end($this->intents);

        return $last ? $last['order']->total_cents->cents : null;
    }
}
