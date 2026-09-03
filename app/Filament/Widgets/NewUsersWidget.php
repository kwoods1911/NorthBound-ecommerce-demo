<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class NewUsersWidget extends BaseWidget
{
    protected static ?string $heading = 'New users';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(User::query()->latest()->limit(10))
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\IconColumn::make('is_admin')->label('Admin')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('Joined')->dateTime('M j, Y'),
            ])
            ->recordUrl(fn () => UserResource::getUrl('index'));
    }
}
