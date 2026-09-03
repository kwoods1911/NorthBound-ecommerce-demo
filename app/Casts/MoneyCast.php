<?php

namespace App\Casts;

use App\Support\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Casts an integer `*_cents` column to/from an App\Support\Money value object.
 *
 * Nullable columns round-trip null. Assigning null, an int (cents), or a Money
 * instance are all accepted when setting.
 *
 * @implements CastsAttributes<Money|null, Money|int|null>
 */
class MoneyCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        return Money::fromCents((int) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Money) {
            return $value->cents;
        }

        return (int) $value;
    }
}
