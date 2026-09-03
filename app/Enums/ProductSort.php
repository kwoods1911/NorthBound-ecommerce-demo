<?php

namespace App\Enums;

enum ProductSort: string
{
    case Featured = 'featured';
    case PriceLowHigh = 'price-low-high';
    case PriceHighLow = 'price-high-low';
    case Newest = 'newest';

    public function label(): string
    {
        return match ($this) {
            self::Featured => 'Featured',
            self::PriceLowHigh => 'Price: low to high',
            self::PriceHighLow => 'Price: high to low',
            self::Newest => 'Newest',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $sort) => ['value' => $sort->value, 'label' => $sort->label()],
            self::cases(),
        );
    }
}
