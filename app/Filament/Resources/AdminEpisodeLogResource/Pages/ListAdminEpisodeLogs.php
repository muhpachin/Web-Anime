<?php

namespace App\Filament\Resources\AdminEpisodeLogResource\Pages;

use App\Filament\Resources\AdminEpisodeLogResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdminEpisodeLogs extends ListRecords
{
    protected static string $resource = AdminEpisodeLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
