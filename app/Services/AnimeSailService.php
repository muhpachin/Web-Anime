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
    
    /**
     * Search anime on AnimeSail
     */
    public function searchAnime($query)
    {
        try {
            $response = Http::get("{$this->baseUrl}/", [
                's' => $query,
            ]);

            if (!$response->successful()) {
                return [];
            }

            $crawler = new Crawler($response->body());
            $results = [];

            $crawler->filter('.post-item')->each(function (Crawler $node) use (&$results) {
                try {
                    $title = $node->filter('.entry-title a')->text();
                    $url = $node->filter('.entry-title a')->attr('href');
                    
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
            $response = Http::get($animeUrl);

            if (!$response->successful()) {
                return null;
            }

            $crawler = new Crawler($response->body());
            
            $episodes = [];
            
            // Extract episode links
            $crawler->filter('.episode-list a, .episodelist a, .entry-content a')->each(function (Crawler $node) use (&$episodes) {
                try {
                    $text = $node->text();
                    $href = $node->attr('href');
                    
                    // Check if this looks like an episode link
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
                    // Skip invalid entries
                }
            });

            return [
                'episodes' => $episodes,
            ];
        } catch (\Exception $e) {
            \Log::error("AnimeSail anime details failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get episode video servers from episode page
     */
    public function getEpisodeServers($episodeUrl)
    {
        try {
            $response = Http::get($episodeUrl);

            if (!$response->successful()) {
                return [];
            }

            $crawler = new Crawler($response->body());
            $servers = [];

            // Look for iframes (video embeds)
            $crawler->filter('iframe')->each(function (Crawler $node) use (&$servers) {
                try {
                    $src = $node->attr('src');
                    
                    if ($this->isValidVideoUrl($src)) {
                        $serverName = $this->getServerName($src);
                        
                        $servers[] = [
                            'name' => $serverName,
                            'url' => $src,
                            'type' => 'iframe',
                        ];
                    }
                } catch (\Exception $e) {
                    // Skip invalid entries
                }
            });

            // Look for video links in content
            $crawler->filter('.entry-content a, .server-list a')->each(function (Crawler $node) use (&$servers) {
                try {
                    $href = $node->attr('href');
                    $text = $node->text();
                    
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
            \Log::error("AnimeSail episode servers failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Sync episodes and servers for an anime
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

                // Fetch video servers for this episode
                $servers = $this->getEpisodeServers($episodeData['url']);
                
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

                if ($wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }

                // Rate limiting
                usleep(500000); // 500ms delay between episode requests
                
            } catch (\Exception $e) {
                $errors[] = "Episode {$episodeData['number']}: " . $e->getMessage();
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    /**
     * Extract episode number from text
     */
    protected function extractEpisodeNumber($text)
    {
        if (preg_match('/(?:episode|ep)\s*(\d+)/i', $text, $matches)) {
            return (int)$matches[1];
        }
        
        if (preg_match('/\b(\d+)\b/', $text, $matches)) {
            return (int)$matches[1];
        }

        return null;
    }

    /**
     * Check if URL is a valid video URL
     */
    protected function isValidVideoUrl($url)
    {
        if (empty($url)) return false;
        
        $videoHosts = [
            'youtube.com',
            'youtu.be',
            'mp4upload.com',
            'streamtape.com',
            'doodstream.com',
            'streamsb.net',
            'fembed.com',
            'dailymotion.com',
        ];

        foreach ($videoHosts as $host) {
            if (stripos($url, $host) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get server name from URL
     */
    protected function getServerName($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        
        $nameMap = [
            'youtube.com' => 'YouTube',
            'youtu.be' => 'YouTube',
            'mp4upload.com' => 'MP4Upload',
            'streamtape.com' => 'StreamTape',
            'doodstream.com' => 'DoodStream',
            'streamsb.net' => 'StreamSB',
            'fembed.com' => 'Fembed',
            'dailymotion.com' => 'Dailymotion',
        ];

        foreach ($nameMap as $domain => $name) {
            if (stripos($host, $domain) !== false) {
                return $name;
            }
        }

        return $host ?? 'Unknown';
    }
}
