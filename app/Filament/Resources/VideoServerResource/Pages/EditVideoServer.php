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

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getSaveAndCopyFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getSaveAndCopyFormAction(): Actions\Action
    {
        return Actions\Action::make('saveAndCopy')
            ->label('Save & Copy')
            ->action('saveAndCopy')
            ->color('success')
            ->keyBindings(['mod+shift+s']);
    }

    public function saveAndCopy(): void
    {
        $this->save();
        
        $original = $this->getRecord();
        $newServer = $original->replicate();
        $newServer->server_name = $original->server_name . ' (Copy)';
        $newServer->save();

        $this->redirect(static::getResource()::getUrl('edit', ['record' => $newServer->id]));
    }
}
