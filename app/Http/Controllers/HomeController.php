<?php

namespace App\Http\Controllers;

use App\Models\Anime;
use App\Models\Genre;

class HomeController extends Controller
{
    /**
     * Display the homepage.
     */
    public function index()
    {
        $featuredAnimes = Anime::where('featured', true)
            ->with('genres', 'episodes')
            ->limit(5)
            ->get();

        $latestEpisodes = Anime::with(['episodes' => fn ($q) => $q->latest()->limit(1)])
            ->has('episodes')
            ->orderBy('updated_at', 'desc')
            ->limit(12)
            ->get();

        $popularAnimes = Anime::with('genres')
            ->orderBy('rating', 'desc')
            ->limit(10)
            ->get();

        $genres = Genre::all();

        return view('home', [
            'featuredAnimes' => $featuredAnimes,
            'latestEpisodes' => $latestEpisodes,
            'popularAnimes' => $popularAnimes,
            'genres' => $genres,
        ]);
    }

    /**
     * Search animes by title, status, type, or genre.
     */
    public function search()
    {
        $query = Anime::query();

        if (request('search')) {
            $search = request('search');
            $query->where('title', 'like', "%{$search}%")
                ->orWhere('synopsis', 'like', "%{$search}%");
        }

        if (request('genre')) {
            $query->whereHas('genres', fn ($q) => $q->where('id', request('genre')));
        }

        if (request('status')) {
            $query->where('status', request('status'));
        }

        if (request('type')) {
            $query->where('type', request('type'));
        }

        $animes = $query->with('genres', 'episodes')
            ->orderBy('updated_at', 'desc')
            ->paginate(12);

        $genres = Genre::all();

        return view('search', [
            'animes' => $animes,
            'genres' => $genres,
        ]);
    }
}
