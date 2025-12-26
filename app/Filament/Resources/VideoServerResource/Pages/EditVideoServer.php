<?php

namespace App\Filament\Resources\VideoServerResource\Pages;

use App\Filament\Resources\VideoServerResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVideoServer extends EditRecord
{
    protected static string $resource = VideoServerResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
