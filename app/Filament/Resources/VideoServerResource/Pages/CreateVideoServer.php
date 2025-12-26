<?php

namespace App\Filament\Resources\VideoServerResource\Pages;

use App\Filament\Resources\VideoServerResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateVideoServer extends CreateRecord
{
    protected static string $resource = VideoServerResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCreateAndCopyFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getCreateAndCopyFormAction(): Actions\Action
    {
        return Actions\Action::make('createAndCopy')
            ->label('Create & Copy')
            ->action('createAndCopy')
            ->color('success')
            ->keyBindings(['mod+shift+s']);
    }

    public function createAndCopy(): void
    {
        $this->create();
        
        $original = $this->getRecord();
        $newServer = $original->replicate();
        $newServer->server_name = $original->server_name . ' (Copy)';
        $newServer->save();

        $this->redirect(static::getResource()::getUrl('edit', ['record' => $newServer->id]));
    }
}
