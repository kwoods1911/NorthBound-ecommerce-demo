<?php

namespace App\Filament\Support;

use App\Support\Money;
use Filament\Tables\Columns\TextColumn;

/**
 * Table column for an integer `*_cents` value or a hydrated {@see Money}.
 * Renders "$129.00"; a null value shows an em dash.
 */
class MoneyColumn
{
    public static function make(string $name): TextColumn
    {
        return TextColumn::make($name)
            ->alignEnd()
            ->sortable()
            ->formatStateUsing(fn ($state): string => match (true) {
                $state instanceof Money => $state->format(),
                $state === null => '—',
                default => Money::fromCents((int) $state)->format(),
            });
    }
}
