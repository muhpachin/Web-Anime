<?php

namespace App\Filament\Resources\AnimeResource\Pages;

use App\Filament\Resources\AnimeResource;
use App\Models\Anime;
// PERBAIKAN: Gunakan namespace Resource Page agar method ::route() tersedia
use Filament\Resources\Pages\Page;

class ListEmptyAnimes extends Page
{
    protected static string $resource = AnimeResource::class;

    // Mengarah ke file blade di resources/views/filament/resources/anime-resource/pages/
    protected static string $view = 'filament.resources.anime-resource.pages.list-empty-animes';

    public $animes;

    public function mount()
    {
        // Query: Mencari anime tanpa episode ATAU episode tanpa video server
        $this->animes = Anime::whereDoesntHave('episodes')
            ->orWhereHas('episodes', function($q) {
                $q->doesntHave('videoServers');
            })
            ->get();
    }
}