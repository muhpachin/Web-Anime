<?php

namespace App\Filament\Resources\EpisodeResource\Pages;

use App\Filament\Resources\EpisodeResource;
use App\Models\AdminEpisodeLog;
use Filament\Resources\Pages\CreateRecord;

class CreateEpisode extends CreateRecord
{
    protected static string $resource = EpisodeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (auth()->check()) {
            $data['created_by'] = auth()->id();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $user = auth()->user();

        if (!$user || !$user->isAdmin() || $user->isSuperAdmin()) {
            return;
        }

        AdminEpisodeLog::firstOrCreate(
            [
                'user_id' => $user->id,
                'episode_id' => $this->record->id,
            ],
            [
                'amount' => AdminEpisodeLog::DEFAULT_AMOUNT,
                'status' => AdminEpisodeLog::STATUS_PENDING,
            ]
        );
    }
}
