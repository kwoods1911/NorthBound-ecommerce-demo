<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Str;
use RuntimeException;
use Stripe\Event;
use Stripe\PaymentIntent;
use Stripe\StripeClient;
use Stripe\Webhook;

/**
 * The only place the Stripe SDK is touched. The client is built lazily so that
 * pages which never call Stripe (e.g. the checkout screen) still work before
 * test keys are configured. Swapped for a fake in tests.
 */
class StripeGateway
{
    private ?StripeClient $client = null;

    public function createPaymentIntent(Order $order): PaymentIntent
    {
        return $this->client()->paymentIntents->create([
            'amount' => $order->total_cents->cents,
            'currency' => config('shop.checkout.currency'),
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ],
        ], [
            // Unique per creation. Guards the SDK's own network-retry, without
            // risking a stale key if an order number is ever reused.
            'idempotency_key' => (string) Str::uuid(),
        ]);
    }

    public function retrievePaymentIntent(string $id): PaymentIntent
    {
        return $this->client()->paymentIntents->retrieve($id);
    }

    public function constructWebhookEvent(string $payload, string $signature): Event
    {
        return Webhook::constructEvent(
            $payload,
            $signature,
            (string) config('services.stripe.webhook_secret'),
        );
    }

    private function client(): StripeClient
    {
        $secret = config('services.stripe.secret');

        if (! $secret) {
            throw new RuntimeException('STRIPE_SECRET is not configured.');
        }

        return $this->client ??= new StripeClient($secret);
    }
}
