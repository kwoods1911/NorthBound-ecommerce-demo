<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HeroImageResource\Pages;
use App\Models\HeroImage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HeroImageResource extends Resource
{
    protected static ?string $model = HeroImage::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Image')
                ->schema([
                    Forms\Components\FileUpload::make('image_path')
                        ->label('Hero image')
                        ->image()
                        ->disk('public')
                        ->directory('hero')
                        ->imageEditor()
                        ->imageEditorAspectRatios(['16:9'])
                        ->helperText('Landscape works best. Leave empty to use the plain fallback hero.'),
                ]),

            Forms\Components\Section::make('Content')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('headline')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('subheading')
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('cta_label')
                        ->label('CTA label')
                        ->maxLength(60),
                    Forms\Components\TextInput::make('cta_url')
                        ->label('CTA URL')
                        ->maxLength(255)
                        ->helperText('Relative path (e.g. /new-arrivals) or a full URL.'),
                ]),

            Forms\Components\Section::make('Visibility')
                ->columns(2)
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->helperText('Activating this hero deactivates every other one.'),
                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
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
                Tables\Columns\TextColumn::make('headline')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->alignEnd()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHeroImages::route('/'),
            'create' => Pages\CreateHeroImage::route('/create'),
            'edit' => Pages\EditHeroImage::route('/{record}/edit'),
        ];
    }
}
