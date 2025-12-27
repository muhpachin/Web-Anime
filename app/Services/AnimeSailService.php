<?php

namespace App\Services;

use App\Models\Anime;
use App\Models\Episode;
use App\Models\VideoServer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class AnimeSailService
{
    protected $baseUrl = 'https://animesail.in';

    public function __construct()
    {
        $this->baseUrl = config('services.animesail.base_url', $this->baseUrl);
    }

    protected function http()
    {
        $verify = (bool) config('services.animesail.verify_ssl', true);
        return \Illuminate\Support\Facades\Http::withOptions(['verify' => $verify])
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])
            ->timeout(10)
            ->retry(1, 100);
    }

    /**
     * Get HTTP client (public wrapper)
     */
    public function getHttpClient()
    {
        return $this->http();
    }
    
    /**
     * Search anime on AnimeSail
     */
    public function searchAnime($query)
    {
        try {
            $searchUrl = rtrim($this->baseUrl, '/') . '/?s=' . urlencode($query);
            $response = $this->http()->get($searchUrl);

            if (!$response->successful()) {
                return [];
            }

            $crawler = new Crawler($response->body());
            $results = [];

            $crawler->filter('.post-item, .post, .search-result, .entry-title')->each(function (Crawler $node) use (&$results) {
                try {
                    $linkNode = $node->filter('a')->first();
                    $title = $linkNode->text();
                    $url = $linkNode->attr('href');
                    
                    $results[] = [
                        'title' => trim($title),
                        'url' => $url,
                        'slug' => basename(parse_url($url, PHP_URL_PATH)),
                    ];
                } catch (\Exception $e) {
                    // Skip invalid entries
                }
            });

            return $results;
        } catch (\Exception $e) {
            \Log::error("AnimeSail search failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get anime details page
     */
    public function getAnimeDetails($animeUrl)
    {
        try {
            $response = $this->http()->get($animeUrl);

            if (!$response->successful()) {
                \Log::warning("AnimeSail: Failed to fetch {$animeUrl}, status: " . $response->status());
                return null;
            }

            $html = $response->body();
            $episodes = [];
            
            // Try to extract episode links from HTML
            preg_match_all('/<a\s+[^>]*href\s*=\s*["\']([^"\']*episode[^"\']*)["\'][^>]*>([^<]*)<\/a>/i', $html, $matches, PREG_SET_ORDER);
            
            if (!empty($matches)) {
                \Log::info("AnimeSail: Found " . count($matches) . " episode links in HTML");
                
                $scheme = parse_url($animeUrl, PHP_URL_SCHEME) ?: 'https';
                $host = parse_url($animeUrl, PHP_URL_HOST);
                $base = ($scheme && $host) ? ($scheme . '://' . $host) : rtrim($this->baseUrl, '/');
                
                foreach ($matches as $match) {
                    $href = $match[1];
                    $text = trim($match[2]);
                    
                    if (strpos($href, '//') === 0) {
                        $href = 'https:' . $href;
                    } elseif ($href && !preg_match('/^https?:/i', $href)) {
                        $href = rtrim($base, '/') . '/' . ltrim($href, '/');
                    }
                    
                    $number = $this->extractEpisodeNumber($text . ' ' . $href);
                    
                    if ($number) {
                        $exists = false;
                        foreach ($episodes as $ep) {
                            if ($ep['number'] === $number) {
                                $exists = true;
                                break;
                            }
                        }
                        
                        if (!$exists) {
                            $episodes[] = [
                                'number' => $number,
                                'title' => !empty($text) ? $text : "Episode {$number}",
                                'url' => $href,
                            ];
                        }
                    }
                }
                
                usort($episodes, fn($a, $b) => $a['number'] <=> $b['number']);
                \Log::info("AnimeSail: Extracted " . count($episodes) . " unique episodes from HTML");
                
                return ['episodes' => $episodes];
            }
            
            // Fallback: use pattern-based generation
            \Log::warning("AnimeSail: No episode links in HTML, using pattern-based generation");
            return $this->generatePatternBasedEpisodes($animeUrl);
            
        } catch (\Exception $e) {
            \Log::error("AnimeSail anime details failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse provided HTML to extract episode links (offline/pasted HTML support)
     */
    public function getAnimeDetailsFromHtml(?string $animeUrl, string $html): array
    {
        $episodes = [];

        // Prefer full anchors that contain "episode" in href
        preg_match_all('/<a\s+[^>]*href\s*=\s*["\']([^"\']*episode[^"\']*)["\'][^>]*>([^<]*)<\/a>/i', $html, $matches, PREG_SET_ORDER);

        $scheme = $animeUrl ? (parse_url($animeUrl, PHP_URL_SCHEME) ?: 'https') : 'https';
        $host = $animeUrl ? parse_url($animeUrl, PHP_URL_HOST) : parse_url($this->baseUrl, PHP_URL_HOST);
        $base = ($scheme && $host) ? ($scheme . '://' . $host) : rtrim($this->baseUrl, '/');

        // Try to infer slug from the page URL for stricter matching later
        $slug = null;
        if ($animeUrl && preg_match('#/anime/([^/]+)/?#', $animeUrl, $sm)) {
            $slug = $sm[1];
        }

        foreach ($matches as $match) {
            $href = $match[1];
            $text = trim($match[2]);

            // Make relative URLs absolute
            if (strpos($href, '//') === 0) {
                $href = 'https:' . $href;
            } elseif ($href && !preg_match('/^https?:/i', $href)) {
                $href = rtrim($base, '/') . '/' . ltrim($href, '/');
            }

            $number = $this->extractEpisodeNumber($text . ' ' . $href);

            if ($number) {
                $exists = false;
                foreach ($episodes as $ep) {
                    if ($ep['number'] === $number) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $episodes[] = [
                        'number' => $number,
                        'title' => !empty($text) ? $text : "Episode {$number}",
                        'url' => $href,
                    ];
                }
            }
        }

        // Fallback: parse anchors in the episode list area only (avoid sidebar/history noise)
        if (empty($episodes)) {
            try {
                $crawler = new Crawler($html);
                $crawler->filter('ul.daftar a, .eps-list a, .epslink a')->each(function (Crawler $node) use (&$episodes, $base, $slug) {
                    try {
                        $href = $node->attr('href') ?? '';
                        $text = trim($node->text(''));

                        if (strpos($href, '//') === 0) {
                            $href = 'https:' . $href;
                        } elseif ($href && !preg_match('/^https?:/i', $href)) {
                            $href = rtrim($base, '/') . '/' . ltrim($href, '/');
                        }

                        // Require URL to contain the inferred slug if available to avoid picking unrelated links
                        if ($slug && stripos($href, $slug) === false) {
                            return;
                        }

                        $number = $this->extractEpisodeNumber($text . ' ' . $href);

                        if ($number) {
                            foreach ($episodes as $ep) {
                                if ($ep['number'] === $number) {
                                    return; // already captured
                                }
                            }

                            $episodes[] = [
                                'number' => $number,
                                'title' => !empty($text) ? $text : "Episode {$number}",
                                'url' => $href,
                            ];
                        }
                    } catch (\Exception $e) {
                        // ignore node-level errors
                    }
                });
            } catch (\Exception $e) {
                \Log::warning('AnimeSail: fallback DOM parse failed: ' . $e->getMessage());
            }
        }

        // Sort ascending by episode number
        usort($episodes, fn($a, $b) => $a['number'] <=> $b['number']);
        \Log::info("AnimeSail: Extracted " . count($episodes) . " episodes from provided HTML");

        return ['episodes' => $episodes];
    }

    /**
     * Sync episodes using provided HTML (no network needed for anime page)
     */
    public function syncEpisodesFromHtml(Anime $anime, string $html, ?string $animeUrl = null): array
    {
        $details = $this->getAnimeDetailsFromHtml($animeUrl, $html);
        if (empty($details['episodes'])) {
            return ['created' => 0, 'updated' => 0, 'errors' => ['No episodes found in HTML']];
        }

        $created = 0; $updated = 0; $errors = [];

        foreach ($details['episodes'] as $episodeData) {
            try {
                $episodeUrl = $episodeData['url'];

                $slug = \Illuminate\Support\Str::slug("{$anime->title} Episode {$episodeData['number']}");

                $episode = Episode::updateOrCreate(
                    [
                        'anime_id' => $anime->id,
                        'episode_number' => $episodeData['number'],
                    ],
                    [
                        'title' => $episodeData['title'],
                        'slug' => $slug,
                    ]
                );

                $wasRecentlyCreated = $episode->wasRecentlyCreated;

                // Fetch video servers for this episode
                $servers = $this->getEpisodeServers($episodeUrl);
                foreach ($servers as $serverData) {
                    VideoServer::updateOrCreate(
                        [
                            'episode_id' => $episode->id,
                            'embed_url' => $serverData['url'],
                        ],
                        [
                            'server_name' => $serverData['name'],
                            'is_active' => true,
                        ]
                    );
                }

                if ($wasRecentlyCreated) { $created++; } else { $updated++; }

                usleep(300000); // 300ms pacing to be gentle
            } catch (\Exception $e) {
                $errors[] = "Episode {$episodeData['number']}: " . $e->getMessage();
            }
        }

        return ['created' => $created, 'updated' => $updated, 'errors' => $errors];
    }

    /**
     * Generate episodes using pattern (fallback for JS-rendered pages)
     */
    protected function generatePatternBasedEpisodes($animeUrl)
    {
        if (!preg_match('/\/anime\/([^\/]+)\/?$/', $animeUrl, $matches)) {
            \Log::error("AnimeSail: Could not extract slug from: {$animeUrl}");
            return ['episodes' => []];
        }
        
        $slug = $matches[1];
        \Log::info("AnimeSail: Generating pattern-based episodes for slug: {$slug}");
        
        $episodes = [];
        
        // Try episodes 1-100 with validation
        for ($i = 1; $i <= 100; $i++) {
            $episodeUrl = $this->baseUrl . "/{$slug}-episode-{$i}/";
            
            try {
                $response = $this->http()->get($episodeUrl);
                
                if ($response->successful()) {
                    $episodes[] = [
                        'number' => $i,
                        'title' => "Episode {$i}",
                        'url' => $episodeUrl,
                    ];
                } else if ($response->status() === 404) {
                    \Log::info("AnimeSail: Episode {$i} not found, stopping");
                    break;
                }
            } catch (\Exception $e) {
                \Log::warning("AnimeSail: Error checking episode {$i}, stopping");
                break;
            }
        }
        
        \Log::info("AnimeSail: Generated " . count($episodes) . " episodes");
        return ['episodes' => $episodes];
    }

    /**
     * Get episode video servers
     */
    public function getEpisodeServers($episodeUrl)
    {
        try {
            $response = $this->http()->get($episodeUrl);

            if (!$response->successful()) {
                return [];
            }

            $crawler = new Crawler($response->body());
            $servers = [];
            $scheme = parse_url($episodeUrl, PHP_URL_SCHEME) ?: 'https';
            $host = parse_url($episodeUrl, PHP_URL_HOST);
            $base = ($scheme && $host) ? ($scheme . '://' . $host) : rtrim($this->baseUrl, '/');

            // Look for iframes
            $crawler->filter('iframe')->each(function (Crawler $node) use (&$servers, $base) {
                try {
                    $src = $node->attr('src') ?? $node->attr('data-src');
                    if ($src && strpos($src, '//') === 0) {
                        $src = 'https:' . $src;
                    } elseif ($src && !preg_match('/^https?:/i', $src)) {
                        $src = rtrim($base, '/') . '/' . ltrim($src, '/');
                    }
                    
                    if ($this->isValidVideoUrl($src)) {
                        $serverName = $this->getServerName($src);
                        
                        $servers[] = [
                            'name' => $serverName,
                            'url' => $src,
                            'type' => 'iframe',
                        ];
                    }
                } catch (\Exception $e) {
                    // Skip
                }
            });

            // Look for video links
            $crawler->filter('.entry-content a, .server-list a, a')->each(function (Crawler $node) use (&$servers, $base) {
                try {
                    $href = $node->attr('href');
                    $text = $node->text();
                    if ($href && strpos($href, '//') === 0) {
                        $href = 'https:' . $href;
                    } elseif ($href && !preg_match('/^https?:/i', $href)) {
                        $href = rtrim($base, '/') . '/' . ltrim($href, '/');
                    }
                    
                    if ($this->isValidVideoUrl($href)) {
                        $serverName = !empty($text) ? trim($text) : $this->getServerName($href);
                        
                        $exists = false;
                        foreach ($servers as $server) {
                            if ($server['url'] === $href) {
                                $exists = true;
                                break;
                            }
                        }
                        
                        if (!$exists) {
                            $servers[] = [
                                'name' => $serverName,
                                'url' => $href,
                                'type' => 'link',
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    // Skip
                }
            });

            return $servers;
        } catch (\Exception $e) {
            \Log::error("AnimeSail episode servers failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Extract episode video servers from provided HTML (no network)
     */
    public function getEpisodeServersFromHtml(string $html, ?string $episodeUrl = null): array
    {
        try {
            $crawler = new Crawler($html);
            $servers = [];
            $scheme = $episodeUrl ? (parse_url($episodeUrl, PHP_URL_SCHEME) ?: 'https') : 'https';
            $host = $episodeUrl ? parse_url($episodeUrl, PHP_URL_HOST) : parse_url($this->baseUrl, PHP_URL_HOST);
            $base = ($scheme && $host) ? ($scheme . '://' . $host) : rtrim($this->baseUrl, '/');

            // Decode default embed from #pembed[data-default] if present (base64-encoded iframe HTML)
            try {
                $pembed = $crawler->filter('#pembed');
                if ($pembed->count()) {
                    $encoded = $pembed->first()->attr('data-default');
                    if (!empty($encoded)) {
                        $decoded = @base64_decode($encoded, true);
                        if ($decoded) {
                            $iframeCrawler = new Crawler($decoded);
                            $iframeCrawler->filter('iframe')->each(function (Crawler $node) use (&$servers, $base) {
                                try {
                                    $src = $node->attr('src') ?? $node->attr('data-src');
                                    if ($src && strpos($src, '//') === 0) {
                                        $src = 'https:' . $src;
                                    } elseif ($src && !preg_match('/^https?:/i', $src)) {
                                        $src = rtrim($base, '/') . '/' . ltrim($src, '/');
                                    }

                                    if ($this->isValidVideoUrl($src)) {
                                        $serverName = $this->getServerName($src);

                                        $exists = false;
                                        foreach ($servers as $s) {
                                            if (($s['url'] ?? null) === $src) { $exists = true; break; }
                                        }
                                        if (!$exists) {
                                            $servers[] = [
                                                'name' => $serverName,
                                                'url' => $src,
                                                'type' => 'iframe',
                                            ];
                                        }
                                    }
                                } catch (\Exception $e) {
                                    // ignore
                                }
                            });
                        }
                    }
                }
            } catch (\Exception $e) {
                // ignore errors in default embed parsing
            }

            // Decode option embeds from select.mirror option[data-em] (base64-encoded iframe HTML)
            try {
                $crawler->filter('select.mirror option[data-em]')->each(function (Crawler $node) use (&$servers, $base) {
                    try {
                        $label = trim($node->text());
                        $encoded = $node->attr('data-em');
                        if (empty($encoded)) return;
                        $decoded = @base64_decode($encoded, true);
                        if (!$decoded) return;
                        $iframeCrawler = new Crawler($decoded);
                        $iframeCrawler->filter('iframe')->each(function (Crawler $iNode) use (&$servers, $base, $label) {
                            try {
                                $src = $iNode->attr('src') ?? $iNode->attr('data-src');
                                if ($src && strpos($src, '//') === 0) {
                                    $src = 'https:' . $src;
                                } elseif ($src && !preg_match('/^https?:/i', $src)) {
                                    $src = rtrim($base, '/') . '/' . ltrim($src, '/');
                                }

                                if ($this->isValidVideoUrl($src)) {
                                    $serverName = !empty($label) ? $label : $this->getServerName($src);

                                    $exists = false;
                                    foreach ($servers as $s) {
                                        if (($s['url'] ?? null) === $src) { $exists = true; break; }
                                    }
                                    if (!$exists) {
                                        $servers[] = [
                                            'name' => $serverName,
                                            'url' => $src,
                                            'type' => 'iframe',
                                        ];
                                    }
                                }
                            } catch (\Exception $e) {
                                // skip
                            }
                        });
                    } catch (\Exception $e) {
                        // skip
                    }
                });
            } catch (\Exception $e) {
                // ignore errors in option embed parsing
            }

            // Look for iframes (video embeds) present directly in the HTML
            $crawler->filter('iframe')->each(function (Crawler $node) use (&$servers, $base) {
                try {
                    $src = $node->attr('src') ?? $node->attr('data-src');
                    if ($src && strpos($src, '//') === 0) {
                        $src = 'https:' . $src;
                    } elseif ($src && !preg_match('/^https?:/i', $src)) {
                        $src = rtrim($base, '/') . '/' . ltrim($src, '/');
                    }
                    
                    if ($this->isValidVideoUrl($src)) {
                        $serverName = $this->getServerName($src);
                        
                        $exists = false;
                        foreach ($servers as $s) {
                            if (($s['url'] ?? null) === $src) { $exists = true; break; }
                        }
                        if (!$exists) {
                            $servers[] = [
                                'name' => $serverName,
                                'url' => $src,
                                'type' => 'iframe',
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    // Skip invalid entries
                }
            });

            // Look for video links in content
            $crawler->filter('.entry-content a, .server-list a, a')->each(function (Crawler $node) use (&$servers, $base) {
                try {
                    $href = $node->attr('href');
                    $text = $node->text();
                    if ($href && strpos($href, '//') === 0) {
                        $href = 'https:' . $href;
                    } elseif ($href && !preg_match('/^https?:/i', $href)) {
                        $href = rtrim($base, '/') . '/' . ltrim($href, '/');
                    }
                    
                    if ($this->isValidVideoUrl($href)) {
                        $serverName = !empty($text) ? trim($text) : $this->getServerName($href);
                        
                        // Avoid duplicates
                        $exists = false;
                        foreach ($servers as $server) {
                            if ($server['url'] === $href) {
                                $exists = true;
                                break;
                            }
                        }
                        
                        if (!$exists) {
                            $servers[] = [
                                'name' => $serverName,
                                'url' => $href,
                                'type' => 'link',
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    // Skip invalid entries
                }
            });

            return $servers;
        } catch (\Exception $e) {
            \Log::error("AnimeSail episode servers from HTML failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Sync a single episode
     */
    public function syncSingleEpisodeFromUrl(Anime $anime, string $episodeUrl)
    {
        $created = 0; $updated = 0; $errors = [];
        try {
            $number = null;
            if (preg_match('/episode[-_\s]?(\d+)/i', $episodeUrl, $m)) {
                $number = (int) $m[1];
            }

            $response = $this->http()->get($episodeUrl);
            if ($response->successful()) {
                $html = $response->body();
                $crawler = new Crawler($html);
                try {
                    $h1 = $crawler->filter('h1, .entry-title')->first();
                    $titleText = $h1->count() ? trim($h1->text()) : null;
                    if (!$number && $titleText && preg_match('/(?:episode|ep)\s*(\d+)/i', $titleText, $mm)) {
                        $number = (int) $mm[1];
                    }
                } catch (\Exception $e) {}
            }

            if (!$number) {
                $errors[] = 'Cannot detect episode number from URL/page';
                return ['created' => 0, 'updated' => 0, 'errors' => $errors];
            }

            $slug = Str::slug("{$anime->title} Episode {$number}");
            $episode = Episode::updateOrCreate(
                ['anime_id' => $anime->id, 'episode_number' => $number],
                ['title' => ($titleText ?? ("Episode {$number}")), 'slug' => $slug]
            );

            $wasNew = $episode->wasRecentlyCreated;

            $servers = $this->getEpisodeServers($episodeUrl);
            foreach ($servers as $serverData) {
                VideoServer::updateOrCreate(
                    ['episode_id' => $episode->id, 'embed_url' => $serverData['url']],
                    ['server_name' => $serverData['name'], 'is_active' => true]
                );
            }

            if ($wasNew) $created++; else $updated++;
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
        }

        return ['created' => $created, 'updated' => $updated, 'errors' => $errors];
    }

    /**
     * Sync all episodes for anime
     */
    public function syncEpisodesForAnime(Anime $anime, $animeSailUrl)
    {
        $animeDetails = $this->getAnimeDetails($animeSailUrl);
        
        if (!$animeDetails || empty($animeDetails['episodes'])) {
            return ['created' => 0, 'updated' => 0, 'errors' => ['No episodes found']];
        }

        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($animeDetails['episodes'] as $episodeData) {
            try {
                $episodeUrl = $episodeData['url'];
                
                $slug = Str::slug("{$anime->title} Episode {$episodeData['number']}");
                
                $episode = Episode::updateOrCreate(
                    ['anime_id' => $anime->id, 'episode_number' => $episodeData['number']],
                    ['title' => $episodeData['title'], 'slug' => $slug]
                );

                $wasRecentlyCreated = $episode->wasRecentlyCreated;

                $servers = $this->getEpisodeServers($episodeUrl);
                
                foreach ($servers as $serverData) {
                    VideoServer::updateOrCreate(
                        ['episode_id' => $episode->id, 'embed_url' => $serverData['url']],
                        ['server_name' => $serverData['name'], 'is_active' => true]
                    );
                }

                if ($wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }

                usleep(500000);
                
            } catch (\Exception $e) {
                $errors[] = "Episode {$episodeData['number']}: " . $e->getMessage();
            }
        }

        return ['created' => $created, 'updated' => $updated, 'errors' => $errors];
    }

    /**
     * Extract episode number
     */
    protected function extractEpisodeNumber($text)
    {
        // Prefer explicit episode markers like "episode 12" or "ep12"
        if (preg_match('/(?:episode|ep)[-?_\s]*(\d+)/i', $text, $matches)) {
            return (int) $matches[1];
        }

        // Fallback: pick the last number token (avoids grabbing "Movie 3" instead of the episode "1")
        if (preg_match_all('/\d+/', $text, $all) && !empty($all[0])) {
            $last = end($all[0]);
            return (int) $last;
        }

        return null;
    }

    /**
     * Check if valid video URL
     */
    protected function isValidVideoUrl($url)
    {
        if (empty($url)) return false;
        
        $videoHosts = [
            'youtube.com', 'youtu.be', 'mp4upload.com', 'streamtape.com',
            'doodstream.com', 'streamsb.net', 'fembed.com', 'dailymotion.com', 'acefile.co',
            'mixdrop.', 'krakenfiles.com', 'aghanim.xyz',
        ];

        foreach ($videoHosts as $host) {
            if (stripos($url, $host) !== false) {
                return true;
            }
        }

        // Allow internal AnimeSail player aggregator paths on the base host/IP
        if (stripos($url, '154.26.137.28') !== false && preg_match('#/utils/player/#i', $url)) {
            return true;
        }

        return false;
    }

    /**
     * Get server name
     */
    protected function getServerName($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        
        $nameMap = [
            'youtube.com' => 'YouTube', 'youtu.be' => 'YouTube',
            'mp4upload.com' => 'MP4Upload', 'streamtape.com' => 'StreamTape',
            'doodstream.com' => 'DoodStream', 'streamsb.net' => 'StreamSB',
            'fembed.com' => 'Fembed', 'dailymotion.com' => 'Dailymotion', 'acefile.co' => 'AceFile',
            'mixdrop.' => 'MixDrop', 'krakenfiles.com' => 'KrakenFiles', 'aghanim.xyz' => 'Lokal',
        ];

        foreach ($nameMap as $domain => $name) {
            if (stripos($host, $domain) !== false) {
                return $name;
            }
        }

        return $host ?? 'Unknown';
    }
}
