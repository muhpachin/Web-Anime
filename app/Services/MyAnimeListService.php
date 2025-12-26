<?php

namespace App\Services;

use App\Models\Anime;
use App\Models\Genre;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MyAnimeListService
{
    protected $baseUrl = 'https://api.jikan.moe/v4';
    
    /**
     * Fetch anime by season (current/upcoming)
     */
    public function fetchSeasonalAnime($year = null, $season = null, $limit = 25)
    {
        $year = $year ?? date('Y');
        $season = $season ?? $this->getCurrentSeason();
        
        $response = Http::get("{$this->baseUrl}/seasons/{$year}/{$season}", [
            'page' => 1,
            'limit' => $limit,
        ]);

        if ($response->successful()) {
            return $response->json()['data'] ?? [];
        }

        return [];
    }

    /**
     * Fetch top anime by rating
     */
    public function fetchTopAnime($limit = 25)
    {
        $response = Http::get("{$this->baseUrl}/top/anime", [
            'page' => 1,
            'limit' => $limit,
        ]);

        if ($response->successful()) {
            return $response->json()['data'] ?? [];
        }

        return [];
    }

    /**
     * Search anime by title
     */
    public function searchAnime($query, $limit = 10)
    {
        $response = Http::get("{$this->baseUrl}/anime", [
            'q' => $query,
            'limit' => $limit,
            'order_by' => 'members',
            'sort' => 'desc',
        ]);

        if ($response->successful()) {
            return $response->json()['data'] ?? [];
        }

        return [];
    }

    /**
     * Get anime details by MAL ID
     */
    public function getAnimeDetails($malId)
    {
        $response = Http::get("{$this->baseUrl}/anime/{$malId}/full");

        if ($response->successful()) {
            return $response->json()['data'] ?? null;
        }

        return null;
    }

    /**
     * Sync anime from MAL to database
     */
    public function syncAnime($malData, $downloadImage = true)
    {
        $slug = Str::slug($malData['title']);
        
        // Check if anime already exists
        $anime = Anime::where('slug', $slug)
            ->orWhere('title', $malData['title'])
            ->first();

        $imageUrl = null;
        if ($downloadImage && isset($malData['images']['jpg']['large_image_url'])) {
            $imageUrl = $this->downloadImage($malData['images']['jpg']['large_image_url'], $slug);
        }

        $animeData = [
            'title' => $malData['title'],
            'slug' => $slug,
            'description' => $malData['synopsis'] ?? '',
            'cover_image' => $imageUrl ?? $malData['images']['jpg']['large_image_url'] ?? null,
            'rating' => isset($malData['score']) ? round($malData['score'], 1) : null,
            'status' => $this->mapStatus($malData['status'] ?? 'Unknown'),
            'release_year' => isset($malData['year']) ? (int)$malData['year'] : null,
            'total_episodes' => $malData['episodes'] ?? 0,
        ];

        if ($anime) {
            $anime->update($animeData);
        } else {
            $anime = Anime::create($animeData);
        }

        // Sync genres
        if (isset($malData['genres']) && is_array($malData['genres'])) {
            $this->syncGenres($anime, $malData['genres']);
        }

        return $anime;
    }

    /**
     * Sync genres for anime
     */
    protected function syncGenres($anime, $malGenres)
    {
        $genreIds = [];

        foreach ($malGenres as $malGenre) {
            $genre = Genre::firstOrCreate(
                ['slug' => Str::slug($malGenre['name'])],
                ['name' => $malGenre['name']]
            );
            $genreIds[] = $genre->id;
        }

        $anime->genres()->sync($genreIds);
    }

    /**
     * Download and store image locally
     */
    protected function downloadImage($url, $slug)
    {
        try {
            $response = Http::get($url);
            
            if ($response->successful()) {
                $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
                $filename = "covers/{$slug}-" . time() . ".{$extension}";
                
                Storage::disk('public')->put($filename, $response->body());
                
                return "/storage/{$filename}";
            }
        } catch (\Exception $e) {
            // Log error but don't fail the sync
            \Log::warning("Failed to download image for {$slug}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Map MAL status to local status
     */
    protected function mapStatus($malStatus)
    {
        $statusMap = [
            'Finished Airing' => 'completed',
            'Currently Airing' => 'ongoing',
            'Not yet aired' => 'upcoming',
        ];

        return $statusMap[$malStatus] ?? 'unknown';
    }

    /**
     * Get current season
     */
    protected function getCurrentSeason()
    {
        $month = date('n');
        
        if ($month >= 1 && $month <= 3) return 'winter';
        if ($month >= 4 && $month <= 6) return 'spring';
        if ($month >= 7 && $month <= 9) return 'summer';
        return 'fall';
    }

    /**
     * Rate limiting - Jikan API has 3 requests per second limit
     */
    protected function rateLimit()
    {
        usleep(350000); // 350ms delay between requests
    }
}
