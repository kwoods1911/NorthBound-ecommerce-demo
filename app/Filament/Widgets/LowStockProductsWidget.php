<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ProductResource;
use App\Filament\Support\MoneyColumn;
use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockProductsWidget extends BaseWidget
{
    protected static ?string $heading = 'Low stock';

    protected static ?int $sort = 5;

    public function table(Table $table): Table
    {
        $threshold = (int) config('shop.low_stock_threshold', 5);

        return $table
            ->query(
                Product::query()
                    ->where('stock_quantity', '<=', $threshold)
                    ->orderBy('stock_quantity')
            )
            ->paginated(false)
            ->emptyStateHeading('Nothing running low')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('category.name')->label('Category'),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->alignEnd()
                    ->badge()
                    ->color('danger'),
                MoneyColumn::make('price_cents')->label('Price'),
            ])
            ->recordUrl(fn ($record) => ProductResource::getUrl('edit', ['record' => $record]));
    }
}
