<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestUsers extends BaseWidget
{
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 1;
    
    protected static ?string $heading = '👤 User Terbaru';

    protected function getTableQuery(): Builder
    {
        return User::query()
            ->latest()
            ->limit(5);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label('Nama')
                ->searchable(),
            Tables\Columns\IconColumn::make('is_admin')
                ->label('Admin')
                ->boolean()
                ->trueIcon('heroicon-o-shield-check')
                ->falseIcon('heroicon-o-user'),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Bergabung')
                ->since(),
        ];
    }
}
