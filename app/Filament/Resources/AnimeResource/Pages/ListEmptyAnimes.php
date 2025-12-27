<?php

namespace App\Filament\Resources\AnimeResource\Pages;

use App\Filament\Resources\AnimeResource;
use App\Models\Anime;
use Filament\Resources\Pages\Page;

class ListEmptyAnimes extends Page
{
    protected static string $resource = AnimeResource::class;
    protected static string $view = 'filament.resources.anime-resource.pages.list-empty-animes';

    // Properti untuk menyimpan pilihan filter tahun
    public $year = '';

    // Fungsi untuk mengambil data anime berdasarkan filter
    protected function getAnimes()
    {
        return Anime::withCount([
            'episodes', 
            'episodes as missing_video_count' => function ($query) {
                $query->doesntHave('videoServers');
            }
        ])
        ->where(function($query) {
            $query->whereDoesntHave('episodes')
                  ->orWhereHas('episodes', function($q) {
                      $q->doesntHave('videoServers');
                  });
        })
        // Tambahkan filter tahun jika dipilih
        ->when($this->year, fn ($query) => $query->where('release_year', $this->year))
        ->get();
    }

    // Mengambil daftar tahun unik untuk dropdown filter
    protected function getAvailableYears()
    {
        return Anime::whereNotNull('release_year')
            ->distinct()
            ->orderBy('release_year', 'desc')
            ->pluck('release_year');
    }

    protected function getViewData(): array
    {
        return [
            'animes' => $this->getAnimes(),
            'years' => $this->getAvailableYears(),
        ];
    }
}