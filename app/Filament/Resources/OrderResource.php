<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Support\MoneyEntry;
use App\Models\Order;
use App\Support\Money;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 2;

    /** Orders originate from checkout — never created by hand in the admin. */
    public static function canCreate(): bool
    {
        return false;
    }

    /** Orders are an audit record — not deletable (CLAUDE.md). */
    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Order')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('shipping_name')
                    ->label('Customer')
                    ->description(fn (Order $record) => $record->email)
                    ->searchable(['shipping_name', 'email']),
                Tables\Columns\IconColumn::make('user_id')
                    ->label('Account')
                    ->boolean()
                    ->trueIcon('heroicon-o-user')
                    ->falseIcon('heroicon-o-user-plus')
                    ->tooltip(fn (Order $record) => $record->isGuest() ? 'Guest checkout' : 'Registered user'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state) => $state->label())
                    ->color(fn (OrderStatus $state): string => self::statusColor($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('total_cents')
                    ->label('Total')
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state instanceof Money ? $state->format() : $state),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Placed')
                    ->dateTime('M j, Y g:ia')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Order')
                ->columns(3)
                ->schema([
                    TextEntry::make('order_number')->label('Order number'),
                    TextEntry::make('status')
                        ->badge()
                        ->formatStateUsing(fn (OrderStatus $state) => $state->label())
                        ->color(fn (OrderStatus $state): string => self::statusColor($state)),
                    TextEntry::make('created_at')->label('Placed')->dateTime('M j, Y g:ia'),
                ]),

            Section::make('Customer & shipping')
                ->columns(2)
                ->schema([
                    TextEntry::make('email'),
                    TextEntry::make('shipping_name')->label('Name'),
                    TextEntry::make('shipping_address')
                        ->label('Address')
                        ->state(fn (Order $record) => collect([
                            $record->shipping_line1,
                            $record->shipping_line2,
                            "{$record->shipping_city}, {$record->shipping_state} {$record->shipping_postal_code}",
                            $record->shipping_country,
                        ])->filter()->implode("\n"))
                        ->columnSpanFull(),
                ]),

            Section::make('Items')
                ->schema([
                    RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->columns(5)
                        ->schema([
                            TextEntry::make('product_name')->columnSpan(2),
                            TextEntry::make('variant_label')->label('Variant')->placeholder('—'),
                            MoneyEntry::make('unit_price_cents')->label('Unit price'),
                            TextEntry::make('quantity')->alignEnd(),
                            MoneyEntry::make('unit_cost_cents')->label('Unit cost'),
                            TextEntry::make('sku')->label('SKU')->placeholder('—'),
                            MoneyEntry::make('line_total_cents')->label('Line total'),
                        ]),
                ]),

            Section::make('Totals')
                ->columns(4)
                ->schema([
                    MoneyEntry::make('subtotal_cents')->label('Subtotal'),
                    MoneyEntry::make('shipping_cents')->label('Shipping'),
                    MoneyEntry::make('tax_cents')->label('Tax'),
                    MoneyEntry::make('total_cents')->label('Total'),
                ]),

            Section::make('Status timeline')
                ->columns(4)
                ->schema([
                    TextEntry::make('paid_at')->dateTime()->placeholder('—'),
                    TextEntry::make('shipped_at')->dateTime()->placeholder('—'),
                    TextEntry::make('delivered_at')->dateTime()->placeholder('—'),
                    TextEntry::make('cancelled_at')->dateTime()->placeholder('—'),
                ]),
        ]);
    }

    public static function statusColor(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Pending => 'gray',
            OrderStatus::Paid => 'info',
            OrderStatus::Shipped => 'warning',
            OrderStatus::Delivered => 'success',
            OrderStatus::Cancelled => 'danger',
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
