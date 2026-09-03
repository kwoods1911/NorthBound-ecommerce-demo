<?php

namespace App\Filament\Support;

use App\Casts\MoneyCast;
use App\Support\Money;
use Filament\Forms\Components\TextInput;

/**
 * A dollars-and-cents text input bound to an integer `*_cents` column.
 *
 * The DB stores minor units and the model hydrates them into {@see Money}
 * via {@see MoneyCast}. This component is the single seam that
 * converts between the two for the admin panel — nothing else should do
 * the ×100 / ÷100 by hand.
 */
class MoneyInput
{
    public static function make(string $name): TextInput
    {
        return TextInput::make($name)
            ->prefix('$')
            ->numeric()
            ->minValue(0)
            ->step('0.01')
            ->formatStateUsing(fn ($state): ?string => $state instanceof Money
                ? number_format($state->cents / 100, 2, '.', '')
                : $state)
            ->dehydrateStateUsing(fn ($state): ?int => $state === null || $state === ''
                ? null
                : (int) round((float) $state * 100));
    }
}
