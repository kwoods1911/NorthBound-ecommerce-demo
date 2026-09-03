<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** Timestamp column that records when the order entered this status, if any. */
    public function timestampColumn(): ?string
    {
        return match ($this) {
            self::Paid => 'paid_at',
            self::Shipped => 'shipped_at',
            self::Delivered => 'delivered_at',
            self::Cancelled => 'cancelled_at',
            self::Pending => null,
        };
    }

    /**
     * Statuses whose revenue counts towards reporting — payment has cleared.
     *
     * @return array<int, self>
     */
    public static function revenueRecognised(): array
    {
        return [self::Paid, self::Shipped, self::Delivered];
    }

    /** Statuses an order in this status is allowed to move to. */
    public function nextStates(): array
    {
        return match ($this) {
            self::Pending => [self::Paid, self::Cancelled],
            self::Paid => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Delivered],
            self::Delivered, self::Cancelled => [],
        };
    }
}
