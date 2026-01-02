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

    // ... (Bagian __construct dan http client biarkan sama, skip ke bawah) ...
    public function __construct()
    {
        $this->baseUrl = config('services.animesail.base_url', $this->baseUrl);
    }
    
    protected function http()
    {
        $verify = (bool) config('services.animesail.verify_ssl', true);
        return Http::withOptions(['verify' => $verify])
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36'])
            ->timeout(10)->retry(1, 100);
    }

    /**
     * Search anime pada AnimeSail (HTML fetch)
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
                    // skip invalid entry
                }
            });

            return $results;
        } catch (\Exception $e) {
            \Log::error("AnimeSail search failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil detail anime langsung dari URL
     */
    public function getAnimeDetails($animeUrl)
    {
        try {
            $response = $this->http()->get($animeUrl);
            if (!$response->successful()) {
                return null;
            }

            return $this->parseEpisodeList($response->body(), $animeUrl);
        } catch (\Exception $e) {
            \Log::error("AnimeSail anime details failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse episode list dari HTML upload (tanpa request jaringan)
     */
    public function getAnimeDetailsFromHtml(?string $animeUrl, string $html): array
    {
        try {
            return $this->parseEpisodeList($html, $animeUrl);
        } catch (\Exception $e) {
            return ['episodes' => []];
        }
    }

    /**
     * Parser reusable untuk daftar episode
     */
    protected function parseEpisodeList(string $html, ?string $baseUrl = null): array
    {
        $crawler = new Crawler($html);
        $episodes = [];

        $scheme = $baseUrl ? (parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https') : 'https';
        $host = $baseUrl ? parse_url($baseUrl, PHP_URL_HOST) : parse_url($this->baseUrl, PHP_URL_HOST);
        $base = ($scheme && $host) ? ($scheme . '://' . $host) : rtrim($this->baseUrl, '/');

        $crawler->filter('.episode-list a, .episodelist a, .entry-content a, .eplister a, .episodes a, ul li a')->each(function (Crawler $node) use (&$episodes, $base) {
            try {
                $text = $node->text();
                $href = $node->attr('href');

                if ($href && strpos($href, '//') === 0) {
                    $href = 'https:' . $href;
                } elseif ($href && !preg_match('/^https?:/i', $href)) {
                    $href = rtrim($base, '/') . '/' . ltrim($href, '/');
                }

                if (preg_match('/episode|ep\s*\d+/i', $text)) {
                    $episodeNumber = $this->extractEpisodeNumber($text);
                    if ($episodeNumber) {
                        $episodes[] = [
                            'number' => $episodeNumber,
                            'title' => trim($text),
                            'url' => $href,
                        ];
                    }
                }
            } catch (\Exception $e) {
                // skip invalid entry
            }
        });

        return ['episodes' => $episodes];
    }

    // === BAGIAN UTAMA YANG DIPERBAIKI (Sync dari HTML Upload) ===

    public function syncEpisodesFromHtml(Anime $anime, string $html, ?string $animeUrl = null): array
    {
        // 1. Parse Episode dari HTML
        $details = $this->getAnimeDetailsFromHtml($animeUrl, $html);
        if (empty($details['episodes'])) {
            return ['created' => 0, 'updated' => 0, 'errors' => ['No episodes found in HTML']];
        }

        // 2. Parse Video Server dari HTML (Logika Baru)
        $directServers = $this->getEpisodeServersFromHtml($html, $animeUrl);
        $directEpisodeNum = null;

        // Coba tebak nomor episode dari Judul Halaman
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/si', $html, $m)) {
            $titleText = trim(strip_tags($m[1]));
            $directEpisodeNum = $this->extractEpisodeNumber($titleText);
        }

        $created = 0; $updated = 0; $errors = [];

        foreach ($details['episodes'] as $episodeData) {
            try {
                $slug = Str::slug("{$anime->title} Episode {$episodeData['number']}");
                
                $episode = Episode::updateOrCreate(
                    ['anime_id' => $anime->id, 'episode_number' => $episodeData['number']],
                    ['title' => $episodeData['title'], 'slug' => $slug]
                );

                // LOGIKA PENTING: Jika episode cocok, pakai server dari HTML upload
                if ($directEpisodeNum && $episodeData['number'] === $directEpisodeNum) {
                     $servers = $directServers;
                } else {
                     // Episode lain fetch online (mungkin gagal kalau diproteksi)
                     $servers = $this->getEpisodeServers($episodeData['url']);
                }

                foreach ($servers as $serverData) {
                    VideoServer::updateOrCreate(
                        ['episode_id' => $episode->id, 'embed_url' => $serverData['url']],
                        ['server_name' => $serverData['name'], 'is_active' => true]
                    );
                }

                if ($episode->wasRecentlyCreated) $created++; else $updated++;
            } catch (\Exception $e) {
                $errors[] = "Ep {$episodeData['number']} Error: " . $e->getMessage();
            }
        }

        return ['created' => $created, 'updated' => $updated, 'errors' => $errors];
    }

    // === PERBAIKAN LOGIKA EKSTRAKSI SERVER ===

    public function getEpisodeServersFromHtml(string $html, ?string $episodeUrl = null): array
    {
        try {
            $crawler = new Crawler($html);
            $servers = [];
            $base = 'https://animesail.in'; 

            // 1. Cek Default Player (#pembed)
            try {
                $pembed = $crawler->filter('#pembed');
                if ($pembed->count()) {
                    $encoded = $pembed->first()->attr('data-default');
                    $this->extractServersFromBase64($encoded, $base, $servers, 'Default');
                }
            } catch (\Exception $e) {}

            // 2. Cek Dropdown Mirror (select.mirror option)
            try {
                $crawler->filter('select.mirror option[data-em]')->each(function (Crawler $node) use (&$servers, $base) {
                    $label = trim($node->text());
                    $encoded = $node->attr('data-em');
                    // Label cleaning (hapus resolusi biar bersih)
                    $cleanLabel = trim(preg_replace('/(360p|480p|720p|1080p|HD|SD)/i', '', $label));
                    $this->extractServersFromBase64($encoded, $base, $servers, $label); // Kirim label asli buat deteksi resolusi
                });
            } catch (\Exception $e) {}

            return $servers;
        } catch (\Exception $e) {
            return [];
        }
    }
    
    protected function extractServersFromBase64($encoded, $base, &$servers, $label = null) {
        if (empty($encoded)) return;
        $decoded = @base64_decode($encoded, true);
        if (!$decoded) return;
        
        // PENTING: Gunakan Regex untuk ekstrak SRC karena DomCrawler kadang bingung kalau HTML tidak valid sempurna
        // Regex ini support kutip satu (') dan kutip dua (")
        if (preg_match('/src=["\']([^"\']+)["\']/i', $decoded, $matches)) {
            $src = html_entity_decode($matches[1]);
            
            // Fix Protocol Relative URL (//google.com -> https://google.com)
            if (strpos($src, '//') === 0) {
                $src = 'https:' . $src;
            }
            
            // Masukkan ke list
            $serverName = !empty($label) ? $label : $this->getServerName($src);
            
            // Hindari duplikat
            foreach ($servers as $s) {
                if (($s['url'] ?? null) === $src) return;
            }

            $servers[] = [
                'name' => $serverName,
                'url' => $src,
                'type' => 'iframe',
            ];
        }
    }

    // === LIST SERVER VALID & PENAMAAN ===

    protected function isValidVideoUrl($url)
    {
        return true; // Terima semua URL hasil decode, filter nanti di frontend
    }

    protected function getServerName($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        $nameMap = [
            'youtube.com' => 'YouTube', 'youtu.be' => 'YouTube',
            'mp4upload.com' => 'MP4Upload', 'streamtape.com' => 'StreamTape',
            'doodstream.com' => 'DoodStream', 'streamsb.net' => 'StreamSB',
            'fembed.com' => 'Fembed', 'dailymotion.com' => 'Dailymotion', 
            'acefile.co' => 'AceFile', 'mixdrop' => 'MixDrop', 
            'krakenfiles.com' => 'Kraken', 'aghanim.xyz' => 'Lokal',
            'doply.net' => 'Dodo', 'buzzheavier.com' => 'Buzi'
        ];

        foreach ($nameMap as $domain => $name) {
            if (stripos($host, $domain) !== false) return $name;
        }

        // Deteksi Server Internal (Pixel, Kamado, Pompom)
        if (stripos($url, '154.26.137.28') !== false) {
            if (strpos($url, '/pixel/') !== false) return 'Pixel';
            if (strpos($url, '/kodir2/') !== false) return 'Kamado';
            if (strpos($url, '/pomf/') !== false) return 'Pompom';
            if (strpos($url, '/framezilla/') !== false) return 'Mega';
            return 'VIP Server';
        }

        return $host ?? 'Unknown';
    }
    
    // ... (Fungsi extractEpisodeNumber dll biarkan) ...
    protected function extractEpisodeNumber($text) {
        if (preg_match('/(?:episode|ep)[-?_\s]*(\d+)/i', $text, $matches)) return (int) $matches[1];
        if (preg_match_all('/\d+/', $text, $all) && !empty($all[0])) return (int) end($all[0]);
        return null;
    }
}