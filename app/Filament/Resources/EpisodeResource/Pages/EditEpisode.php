<?php

namespace App\Filament\Resources\EpisodeResource\Pages;

use App\Filament\Resources\EpisodeResource;
use App\Models\AdminEpisodeLog;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEpisode extends EditRecord
{
    protected static string $resource = EpisodeResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $user = auth()->user();

        // Catat semua admin (termasuk superadmin)
        if (!$user || !$user->isAdmin()) {
            return;
        }

        // Cek apakah admin ini sudah punya log untuk episode ini
        $existingLog = AdminEpisodeLog::where('user_id', $user->id)
            ->where('episode_id', $this->record->id)
            ->first();

        // Jika belum ada log, buat log baru (artinya admin ini edit episode orang lain)
        if (!$existingLog) {
            AdminEpisodeLog::create([
                'user_id' => $user->id,
                'episode_id' => $this->record->id,
                'amount' => AdminEpisodeLog::DEFAULT_AMOUNT,
                'status' => AdminEpisodeLog::STATUS_PENDING,
                'note' => 'Episode diedit',
            ]);
        }
    }
}
