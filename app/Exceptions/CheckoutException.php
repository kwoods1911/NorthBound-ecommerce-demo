<?php

namespace App\Exceptions;

use RuntimeException;

class CheckoutException extends RuntimeException
{
    /**
     * @param  array<int, string>  $issues
     */
    public function __construct(string $message, public readonly array $issues = [])
    {
        parent::__construct($message);
    }

    /**
     * @param  array<int, string>  $issues
     */
    public static function stockChanged(array $issues): self
    {
        return new self('Some items are no longer available in the quantity requested.', $issues);
    }

    public static function emptyCart(): self
    {
        return new self('Your cart is empty.');
    }
}
