<?php

namespace App\Http\Controllers;

use App\Models\Anime;

class DetailController extends Controller
{
    /**
     * Display the detail page of an anime.
     */
    public function show(Anime $anime)
    {
        $anime->load('genres', 'episodes.videoServers');

        $relatedAnimes = Anime::whereHas('genres', function ($query) use ($anime) {
            $query->whereIn('genre_id', $anime->genres->pluck('id'));
        })
            ->where('id', '!=', $anime->id)
            ->with('genres')
            ->limit(6)
            ->get();

        return view('detail', [
            'anime' => $anime,
            'relatedAnimes' => $relatedAnimes,
        ]);
    }
}
