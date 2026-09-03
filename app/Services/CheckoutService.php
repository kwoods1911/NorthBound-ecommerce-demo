<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\CheckoutException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Services\Payments\StripeGateway;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(
        private readonly CartService $carts,
        private readonly StripeGateway $stripe,
    ) {
    }

    /**
     * Cart summary plus recomputed shipping / tax / total and any stock issues.
     *
     * @return array<string, mixed>
     */
    public function preview(Cart $cart): array
    {
        $summary = $this->carts->summarize($cart);
        $subtotal = (int) $summary['subtotal']['cents'];
        $shipping = $this->shippingFor($subtotal);
        $tax = $this->taxFor($subtotal);

        return [
            ...$summary,
            'shipping' => Money::fromCents($shipping)->toArray(),
            'tax' => Money::fromCents($tax)->toArray(),
            'total' => Money::fromCents($subtotal + $shipping + $tax)->toArray(),
            'issues' => $this->stockIssues($cart),
        ];
    }

    /**
     * Create a pending order from the cart and a Stripe PaymentIntent for it.
     * All money is recomputed here — the client total is never trusted.
     *
     * @param  array<string, string|null>  $shipping
     * @return array{order: Order, clientSecret: string}
     */
    public function place(Cart $cart, array $shipping): array
    {
        $cart->loadMissing('items.product', 'items.variant');

        if ($cart->items->isEmpty()) {
            throw CheckoutException::emptyCart();
        }

        if ($issues = $this->stockIssues($cart)) {
            throw CheckoutException::stockChanged($issues);
        }

        return DB::transaction(function () use ($cart, $shipping) {
            $subtotal = (int) $cart->items->sum(fn (CartItem $i) => $i->lineTotal()->cents);
            $shippingCents = $this->shippingFor($subtotal);
            $taxCents = $this->taxFor($subtotal);

            $order = Order::create([
                'user_id' => auth()->id(),
                'cart_id' => $cart->id,
                'order_number' => 'TMP-'.Str::random(24),
                'status' => OrderStatus::Pending,
                'email' => $shipping['email'],
                'shipping_name' => $shipping['name'],
                'shipping_line1' => $shipping['line1'],
                'shipping_line2' => $shipping['line2'] ?? null,
                'shipping_city' => $shipping['city'],
                'shipping_state' => $shipping['state'] ?? null,
                'shipping_postal_code' => $shipping['postal_code'],
                'shipping_country' => $shipping['country'],
                'subtotal_cents' => $subtotal,
                'shipping_cents' => $shippingCents,
                'tax_cents' => $taxCents,
                'total_cents' => $subtotal + $shippingCents + $taxCents,
            ]);

            // Order number is derived from the (unique, monotonic) primary key so
            // it can never collide, even after deletions or under concurrency.
            $order->forceFill([
                'order_number' => sprintf('TGC-%s-%04d', $order->created_at->format('Ymd'), $order->id),
            ])->save();

            foreach ($cart->items as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_name' => $item->product->name,
                    'variant_label' => $item->variant?->label() ?: null,
                    'sku' => $item->variant?->sku,
                    'unit_price_cents' => $item->unitPrice()->cents,
                    'unit_cost_cents' => $item->unitCost()->cents,
                    'quantity' => $item->quantity,
                    'line_total_cents' => $item->lineTotal()->cents,
                ]);
            }

            $intent = $this->stripe->createPaymentIntent($order);
            $order->update(['stripe_payment_intent_id' => $intent->id]);

            session()->push('placed_order_ids', $order->id);

            return ['order' => $order, 'clientSecret' => (string) $intent->client_secret];
        });
    }

    /**
     * Mark an order paid, draw down stock, bump sales counts, empty the cart.
     * Idempotent: safe to call from both the webhook and the return redirect,
     * and safe to call twice.
     */
    public function finalizePaid(Order $order, string $paymentIntentId): void
    {
        DB::transaction(function () use ($order, $paymentIntentId) {
            /** @var Order $order */
            $order = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($order->status !== OrderStatus::Pending) {
                return;
            }

            if ($order->stripe_payment_intent_id && $order->stripe_payment_intent_id !== $paymentIntentId) {
                return;
            }

            $order->markPaid($paymentIntentId);

            foreach ($order->items()->get() as $item) {
                if (! $item->product_id) {
                    continue;
                }

                $product = Product::query()->whereKey($item->product_id)->lockForUpdate()->first();

                if (! $product) {
                    continue;
                }

                $product->decrement('stock_quantity', min($item->quantity, $product->stock_quantity));
                $product->increment('sales_count', $item->quantity);
            }

            $this->carts->clear($order->cart);
        });
    }

    private function shippingFor(int $subtotalCents): int
    {
        if ($subtotalCents === 0) {
            return 0;
        }

        return $subtotalCents >= (int) config('shop.checkout.free_shipping_threshold_cents')
            ? 0
            : (int) config('shop.checkout.flat_shipping_cents');
    }

    private function taxFor(int $subtotalCents): int
    {
        return (int) round($subtotalCents * (float) config('shop.checkout.tax_rate'));
    }

    /**
     * @return array<int, string>
     */
    private function stockIssues(Cart $cart): array
    {
        $issues = [];

        foreach ($cart->items as $item) {
            $available = $item->product?->stock_quantity ?? 0;

            if ($item->quantity > $available) {
                $issues[] = $available > 0
                    ? "Only {$available} of \"{$item->product->name}\" left."
                    : "\"{$item->product?->name}\" is now out of stock.";
            }
        }

        return $issues;
    }
}
