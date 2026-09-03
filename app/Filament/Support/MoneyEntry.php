<?php

namespace App\Filament\Support;

use App\Support\Money;
use Filament\Infolists\Components\TextEntry;

/**
 * Infolist counterpart of {@see MoneyColumn}, for the read-only order view.
 */
class MoneyEntry
{
    public static function make(string $name): TextEntry
    {
        return TextEntry::make($name)
            ->formatStateUsing(fn ($state): string => match (true) {
                $state instanceof Money => $state->format(),
                $state === null => '—',
                default => Money::fromCents((int) $state)->format(),
            });
    }
}
