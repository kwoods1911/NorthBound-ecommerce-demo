<?php

namespace App\Support;

use Stringable;

/**
 * Immutable money value object backed by an integer count of minor units (cents).
 * All monetary columns in the database are stored as `*_cents` unsigned integers
 * and hydrated into this object via App\Casts\MoneyCast.
 */
final class Money implements Stringable
{
    public function __construct(
        public readonly int $cents,
        public readonly string $currency = 'USD',
    ) {
    }

    public static function fromCents(int $cents, string $currency = 'USD'): self
    {
        return new self($cents, $currency);
    }

    public static function fromDollars(int|float|string $dollars, string $currency = 'USD'): self
    {
        return new self((int) round((float) $dollars * 100), $currency);
    }

    public function dollars(): float
    {
        return $this->cents / 100;
    }

    public function plus(Money $other): self
    {
        return new self($this->cents + $other->cents, $this->currency);
    }

    public function times(int $factor): self
    {
        return new self($this->cents * $factor, $this->currency);
    }

    /** e.g. "$129.00" */
    public function format(): string
    {
        return '$'.number_format($this->cents / 100, 2);
    }

    /** Value handed to the frontend: raw cents plus a preformatted label. */
    public function toArray(): array
    {
        return [
            'cents' => $this->cents,
            'currency' => $this->currency,
            'formatted' => $this->format(),
        ];
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
