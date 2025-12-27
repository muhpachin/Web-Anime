<?php

namespace App\Filament\Resources\AnimeResource\Pages;

use App\Filament\Resources\AnimeResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAnimes extends ListRecords
{
    protected static string $resource = AnimeResource::class;

    protected function getActions(): array
    {
        return [
            // Tombol tambahan untuk cek anime yang masih kosong
            Actions\Action::make('cek_kosong')
                ->label('Cek Anime Kosong')
                ->color('warning')
                ->icon('heroicon-o-exclamation')
                ->url(fn () => static::getResource()::getUrl('list-empty-animes')),

            Actions\CreateAction::make(),
        ];
    }
}