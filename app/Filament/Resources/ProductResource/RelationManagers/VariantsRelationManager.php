<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Filament\Support\MoneyColumn;
use App\Filament\Support\MoneyInput;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Variants';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('color')
                ->maxLength(255),
            Forms\Components\TextInput::make('size')
                ->maxLength(255),
            Forms\Components\TextInput::make('sku')
                ->label('SKU')
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            MoneyInput::make('price_override_cents')
                ->label('Price override')
                ->helperText('Optional — overrides the product price for this variant.'),
            Forms\Components\TextInput::make('sort_order')
                ->numeric()
                ->default(0),
            Forms\Components\FileUpload::make('image_path')
                ->image()
                ->disk('public')
                ->directory('variants')
                ->helperText('Optional — falls back to the product image.')
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->disk('public')
                    ->label('')
                    ->square(),
                Tables\Columns\TextColumn::make('color'),
                Tables\Columns\TextColumn::make('size'),
                Tables\Columns\TextColumn::make('sku')->label('SKU'),
                MoneyColumn::make('price_override_cents')->label('Price override'),
                Tables\Columns\TextColumn::make('sort_order')->label('Sort')->alignEnd(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
