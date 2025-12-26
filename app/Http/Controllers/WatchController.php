<?php

namespace App\Http\Controllers;

use App\Models\Episode;

class WatchController extends Controller
{
    /**
     * Display the watch page for an episode.
     */
    public function show(Episode $episode)
    {
        $episode->load('anime.genres', 'videoServers');

        $animeEpisodes = $episode->anime->episodes()
            ->orderBy('episode_number', 'asc')
            ->get();

        return view('watch', [
            'episode' => $episode,
            'animeEpisodes' => $animeEpisodes,
        ]);
    }
}
