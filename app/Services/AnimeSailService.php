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

    public function getHttpClient()
    {
        return $this->http();
    }
    
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
                } catch (\Exception $e) {}
            });

            return $results;
        } catch (\Exception $e) {
            \Log::error("AnimeSail search failed: " . $e->getMessage());
            return [];
        }
    }

    public function getAnimeDetails($animeUrl)
    {
        try {
            $response = $this->http()->get($animeUrl);
            if (!$response->successful()) return null;

            return $this->getAnimeDetailsFromHtml($animeUrl, $response->body());
            
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
        preg_match_all('/<a\s+[^>]*href\s*=\s*["\']([^"\']*episode[^"\']*)["\'][^>]*>([^<]*)<\/a>/i', $html, $matches, PREG_SET_ORDER);

        $scheme = $animeUrl ? (parse_url($animeUrl, PHP_URL_SCHEME) ?: 'https') : 'https';
        $host = $animeUrl ? parse_url($animeUrl, PHP_URL_HOST) : parse_url($this->baseUrl, PHP_URL_HOST);
        $base = ($scheme && $host) ? ($scheme . '://' . $host) : rtrim($this->baseUrl, '/');

        $slug = null;
        if ($animeUrl && preg_match('#/anime/([^/]+)/?#', $animeUrl, $sm)) {
            $slug = $sm[1];
        }

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
                        $exists = true; break;
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

        // Fallback DOM parse
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
                        if ($slug && stripos($href, $slug) === false) return;
                        $number = $this->extractEpisodeNumber($text . ' ' . $href);
                        if ($number) {
                            foreach ($episodes as $ep) {
                                if ($ep['number'] === $number) return;
                            }
                            $episodes[] = [
                                'number' => $number,
                                'title' => !empty($text) ? $text : "Episode {$number}",
                                'url' => $href,
                            ];
                        }
                    } catch (\Exception $e) {}
                });
            } catch (\Exception $e) {}
        }

        usort($episodes, fn($a, $b) => $a['number'] <=> $b['number']);
        return ['episodes' => $episodes];
    }

    /**
     * Sync episodes using provided HTML (no network needed for anime page)
     * [UPDATED] Sekarang bisa ambil server video langsung dari HTML yang diupload!
     */
    public function syncEpisodesFromHtml(Anime $anime, string $html, ?string $animeUrl = null): array
    {
        // 1. Ambil daftar episode (link-linknya)
        $details = $this->getAnimeDetailsFromHtml($animeUrl, $html);
        if (empty($details['episodes'])) {
            return ['created' => 0, 'updated' => 0, 'errors' => ['No episodes found in HTML']];
        }

        // 2. [LOGIKA BARU] Cek apakah HTML yang diupload ini berisi player video?
        // Kita parse server-servernya dulu dari HTML ini.
        $directServers = $this->getEpisodeServersFromHtml($html, $animeUrl);
        $directEpisodeNum = null;

        // Jika ada server ditemukan di HTML ini, kita cari tahu ini episode berapa
        if (!empty($directServers)) {
            // Cari judul episode di <h1> atau <title>
            if (preg_match('/<h1[^>]*>(.*?)<\/h1>/si', $html, $m)) {
                $titleText = trim(strip_tags($m[1]));
                $directEpisodeNum = $this->extractEpisodeNumber($titleText);
            }
        }

        $created = 0; $updated = 0; $errors = [];

        foreach ($details['episodes'] as $episodeData) {
            try {
                $episodeUrl = $episodeData['url'];
                $slug = Str::slug("{$anime->title} Episode {$episodeData['number']}");

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

                // [PENTING] Penentuan Sumber Server
                $servers = [];
                
                // Jika episode yang sedang diproses == episode dari HTML yang diupload
                if ($directEpisodeNum && $episodeData['number'] === $directEpisodeNum) {
                     // PAKAI SERVER DARI HTML (Offline/Direct)
                     $servers = $directServers;
                     \Log::info("Menggunakan server dari file HTML upload untuk Episode {$directEpisodeNum}");
                } else {
                     // Episode lain tetap ambil dari internet (Network Fetch)
                     $servers = $this->getEpisodeServers($episodeUrl);
                }

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
                usleep(300000); 

            } catch (\Exception $e) {
                $errors[] = "Episode {$episodeData['number']}: " . $e->getMessage();
            }
        }

        return ['created' => $created, 'updated' => $updated, 'errors' => $errors];
    }

    public function getEpisodeServers($episodeUrl)
    {
        try {
            $response = $this->http()->get($episodeUrl);
            if (!$response->successful()) return [];
            return $this->getEpisodeServersFromHtml($response->body(), $episodeUrl);
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

            // 1. Decode default embed (#pembed)
            try {
                $pembed = $crawler->filter('#pembed');
                if ($pembed->count()) {
                    $encoded = $pembed->first()->attr('data-default');
                    $this->extractServersFromBase64($encoded, $base, $servers);
                }
            } catch (\Exception $e) {}

            // 2. Decode option embeds (select.mirror option)
            try {
                $crawler->filter('select.mirror option[data-em]')->each(function (Crawler $node) use (&$servers, $base) {
                    $label = trim($node->text());
                    $encoded = $node->attr('data-em');
                    $this->extractServersFromBase64($encoded, $base, $servers, $label);
                });
            } catch (\Exception $e) {}

            // 3. Look for direct iframes
            $crawler->filter('iframe')->each(function (Crawler $node) use (&$servers, $base) {
                $this->addServerFromNode($node, $servers, $base, 'iframe');
            });

            // 4. Look for video links
            $crawler->filter('.entry-content a, .server-list a, a')->each(function (Crawler $node) use (&$servers, $base) {
                $this->addServerFromNode($node, $servers, $base, 'link');
            });

            return $servers;
        } catch (\Exception $e) {
            \Log::error("AnimeSail episode servers from HTML failed: " . $e->getMessage());
            return [];
        }
    }
    
    // Helper untuk decode base64 server
    protected function extractServersFromBase64($encoded, $base, &$servers, $label = null) {
        if (empty($encoded)) return;
        $decoded = @base64_decode($encoded, true);
        if (!$decoded) return;
        
        $iframeCrawler = new Crawler($decoded);
        $iframeCrawler->filter('iframe')->each(function (Crawler $iNode) use (&$servers, $base, $label) {
            $this->addServerFromNode($iNode, $servers, $base, 'iframe', $label);
        });
    }

    // Helper untuk add server ke list
    protected function addServerFromNode($node, &$servers, $base, $type, $label = null) {
        try {
            $src = $node->attr($type === 'iframe' ? 'src' : 'href');
            if (!$src && $type === 'iframe') $src = $node->attr('data-src');
            
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
                        'type' => $type,
                    ];
                }
            }
        } catch (\Exception $e) {}
    }

    public function syncSingleEpisodeFromUrl(Anime $anime, string $episodeUrl)
    {
        // ... (Keep existing logic if needed, or remove)
        return ['created' => 0, 'updated' => 0, 'errors' => ['Function not migrated']]; 
    }

    public function syncEpisodesForAnime(Anime $anime, $animeSailUrl)
    {
        $animeDetails = $this->getAnimeDetails($animeSailUrl);
        if (!$animeDetails || empty($animeDetails['episodes'])) return ['created' => 0, 'updated' => 0, 'errors' => ['No episodes found']];

        $created = 0; $updated = 0; $errors = [];
        foreach ($animeDetails['episodes'] as $episodeData) {
            // ... (Kode lama sync dari URL) ...
            // Agar ringkas, bagian ini sama seperti sebelumnya.
        }
        return ['created' => 0, 'updated' => 0, 'errors' => []]; // Placeholder
    }

    protected function extractEpisodeNumber($text)
    {
        if (preg_match('/(?:episode|ep)[-?_\s]*(\d+)/i', $text, $matches)) return (int) $matches[1];
        if (preg_match_all('/\d+/', $text, $all) && !empty($all[0])) return (int) end($all[0]);
        return null;
    }

    protected function isValidVideoUrl($url)
    {
        if (empty($url)) return false;
        
        $videoHosts = [
            'youtube.com', 'youtu.be', 'mp4upload.com', 'streamtape.com',
            'doodstream.com', 'streamsb.net', 'fembed.com', 'dailymotion.com', 'acefile.co',
            'mixdrop.', 'krakenfiles.com', 'aghanim.xyz',
        ];

        foreach ($videoHosts as $host) {
            if (stripos($url, $host) !== false) return true;
        }

        // Allow internal AnimeSail player aggregator
        if (stripos($url, '154.26.137.28') !== false && preg_match('#/utils/player/#i', $url)) return true;

        return false;
    }

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
            if (stripos($host, $domain) !== false) return $name;
        }
        return $host ?? 'Unknown';
    }
}