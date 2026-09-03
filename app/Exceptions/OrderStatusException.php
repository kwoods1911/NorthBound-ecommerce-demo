<?php

namespace App\Exceptions;

use App\Enums\OrderStatus;
use App\Models\Order;
use RuntimeException;

class OrderStatusException extends RuntimeException
{
    public static function illegalTransition(Order $order, OrderStatus $to): self
    {
        return new self(
            "Cannot move order {$order->order_number} from {$order->status->value} to {$to->value}."
        );
    }
}
