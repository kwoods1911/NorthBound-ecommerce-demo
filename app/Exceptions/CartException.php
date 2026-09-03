<?php

namespace App\Exceptions;

use RuntimeException;

class CartException extends RuntimeException
{
    public static function insufficientStock(string $productName, int $available): self
    {
        return new self($available > 0
            ? "Only {$available} of \"{$productName}\" left in stock."
            : "\"{$productName}\" is out of stock.");
    }

    public static function invalidVariant(): self
    {
        return new self('That option is not available for this product.');
    }
}
