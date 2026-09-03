<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers\VariantsRelationManager;
use App\Filament\Support\MoneyColumn;
use App\Filament\Support\MoneyInput;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Details')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                            if ($operation === 'create') {
                                $set('slug', Str::slug($state));
                            }
                        }),
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('Used in the storefront URL.'),
                    Forms\Components\Select::make('category_id')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', Str::slug($state))),
                            Forms\Components\TextInput::make('slug')
                                ->required()
                                ->unique('categories', 'slug'),
                        ]),
                    Forms\Components\Textarea::make('description')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Pricing')
                ->columns(3)
                ->schema([
                    MoneyInput::make('price_cents')
                        ->label('Price')
                        ->required(),
                    MoneyInput::make('cost_price_cents')
                        ->label('Cost price')
                        ->required()
                        ->helperText('What the item costs you — drives profit reporting.'),
                    MoneyInput::make('discount_price_cents')
                        ->label('Discount price')
                        ->lt('price_cents')
                        ->helperText('Leave blank for no discount.'),
                ]),

            Forms\Components\Section::make('Inventory')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('stock_quantity')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    Forms\Components\TextInput::make('sales_count')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Updated automatically when orders are paid.'),
                ]),

            Forms\Components\Section::make('Image')
                ->schema([
                    Forms\Components\FileUpload::make('image_path')
                        ->image()
                        ->disk('public')
                        ->directory('products')
                        ->imageEditor(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->disk('public')
                    ->label('')
                    ->square(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->sortable()
                    ->toggleable(),
                MoneyColumn::make('price_cents')->label('Price'),
                MoneyColumn::make('discount_price_cents')->label('Discount'),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->alignEnd()
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => $state <= config('shop.low_stock_threshold') ? 'danger' : 'gray'),
                Tables\Columns\TextColumn::make('sales_count')
                    ->label('Sold')
                    ->alignEnd()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Low stock only')
                    ->query(fn ($query) => $query->where('stock_quantity', '<=', config('shop.low_stock_threshold'))),
                Tables\Filters\Filter::make('on_sale')
                    ->label('On sale only')
                    ->query(fn ($query) => $query->whereNotNull('discount_price_cents')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            VariantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
