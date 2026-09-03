<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\Payments\StripeGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeGateway $stripe, CheckoutService $checkout): Response
    {
        try {
            $event = $stripe->constructWebhookEvent(
                $request->getContent(),
                $request->header('Stripe-Signature', ''),
            );
        } catch (SignatureVerificationException) {
            return response('Invalid signature.', 400);
        }

        if ($event->type === 'payment_intent.succeeded') {
            /** @var \Stripe\PaymentIntent $intent */
            $intent = $event->data->object;

            $order = Order::query()
                ->where('stripe_payment_intent_id', $intent->id)
                ->when(
                    isset($intent->metadata['order_id']),
                    fn ($q) => $q->orWhere('id', $intent->metadata['order_id']),
                )
                ->first();

            if ($order) {
                $checkout->finalizePaid($order, $intent->id);
            }
        }

        if ($event->type === 'payment_intent.payment_failed') {
            Log::info('Stripe payment failed', ['payment_intent' => $event->data->object->id ?? null]);
        }

        return response('', 200);
    }
}
