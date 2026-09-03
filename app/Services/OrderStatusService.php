<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\OrderStatusException;
use App\Models\Order;

/**
 * Applies admin-driven order status transitions.
 *
 * Scope is deliberately narrow: it moves the order to the new status and
 * stamps the matching `*_at` column. It does NOT touch inventory,
 * `sales_count`, or the cart — that side-effect chain belongs to the Stripe
 * payment path ({@see CheckoutService}) and is out of scope for
 * manual admin transitions in this demo.
 */
class OrderStatusService
{
    public function transition(Order $order, OrderStatus $to): Order
    {
        $allowed = $order->status->nextStates();

        if (! in_array($to, $allowed, true)) {
            throw OrderStatusException::illegalTransition($order, $to);
        }

        $attributes = ['status' => $to];

        if ($column = $to->timestampColumn()) {
            $attributes[$column] = now();
        }

        $order->forceFill($attributes)->save();

        return $order;
    }
}
