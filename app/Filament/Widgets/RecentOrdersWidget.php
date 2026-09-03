<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource;
use App\Support\Money;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentOrdersWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent orders';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(OrderResource::getEloquentQuery()->latest()->limit(10))
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->label('Order'),
                Tables\Columns\TextColumn::make('shipping_name')->label('Customer'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state) => $state->label())
                    ->color(fn (OrderStatus $state) => OrderResource::statusColor($state)),
                Tables\Columns\TextColumn::make('total_cents')
                    ->label('Total')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state) => $state instanceof Money ? $state->format() : $state),
                Tables\Columns\TextColumn::make('created_at')->label('Placed')->dateTime('M j, Y g:ia'),
            ])
            ->recordUrl(fn ($record) => OrderResource::getUrl('view', ['record' => $record]));
    }
}
