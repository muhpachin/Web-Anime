<?php

namespace App\Services;

/**
 * Helper untuk convert URL server video menjadi iframe HTML yang bisa di-embed
 */
class VideoEmbedHelper
{
    /**
     * Extract original URL from AnimeSail proxy URL
     * e.g. from "154.26.137.28/utils/player/framezilla?bsrc=Ly9hY2VmaWxlLmNvL3BsYXllci8xNjk0NDU2OA==" 
     * to "https://acefile.co/player/16944568"
     */
    public static function extractOriginalUrl(string $url): ?string
    {
        // Check if it's an AnimeSail proxy URL
        if (stripos($url, '154.26.137.28') !== false || stripos($url, '/utils/player/') !== false) {
            // Try to extract bsrc parameter
            if (preg_match('/[?&]bsrc=([^&]+)/i', $url, $matches)) {
                $encoded = urldecode($matches[1]);
                $decoded = @base64_decode($encoded, true);
                if ($decoded) {
                    // Add https: if starts with //
                    if (strpos($decoded, '//') === 0) {
                        return 'https:' . $decoded;
                    }
                    if (preg_match('/^https?:/i', $decoded)) {
                        return $decoded;
                    }
                }
            }
            
            // Try to extract id parameter (for gphoto)
            if (preg_match('/[?&]id=([^&]+)/i', $url, $matches)) {
                $encoded = urldecode($matches[1]);
                $decoded = @base64_decode($encoded, true);
                if ($decoded) {
                    return $decoded;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Convert server URL to embeddable iframe HTML
     * Now prefers original URLs over proxy URLs
     */
    public static function toEmbedCode(string $url, ?string $serverName = null): string
    {
        // Already iframe HTML
        if (stripos($url, '<iframe') === 0) {
            // Try to extract and use original URL from iframe src
            if (preg_match('/src=["\']([^"\']+)["\']/i', $url, $matches)) {
                $srcUrl = html_entity_decode($matches[1]);
                $originalUrl = self::extractOriginalUrl($srcUrl);
                if ($originalUrl) {
                    // Replace proxy URL with original URL in iframe
                    return preg_replace('/src=["\'][^"\']+["\']/i', 'src="' . htmlspecialchars($originalUrl, ENT_QUOTES, 'UTF-8') . '"', $url);
                }
            }
            return $url;
        }

        $url = trim($url);
        
        // Try to extract original URL if this is a proxy URL
        $originalUrl = self::extractOriginalUrl($url);
        if ($originalUrl) {
            $url = $originalUrl;
        }

        // aghanim.xyz/tools/lokal (lokal player)
        if (stripos($url, 'aghanim.xyz') !== false && stripos($url, '/tools/lokal/') !== false) {
            return sprintf(
                '<iframe src="%s" id="picasa" frameborder="0" width="100%%" height="100%%" allowfullscreen="allowfullscreen" scrolling="no" allow="autoplay; fullscreen; encrypted-media" referrerpolicy="no-referrer"></iframe>',
                htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
            );
        }

        // General iframe sources (MP4Upload, MixDrop, Kraken, Acefile, etc.) - embed directly
        if (stripos($url, 'mp4upload.com') !== false) {
            return sprintf(
                '<iframe src="%s" frameborder="0" width="100%%" height="100%%" allow="autoplay; fullscreen; encrypted-media" allowfullscreen referrerpolicy="no-referrer"></iframe>',
                htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
            );
        }

        if (stripos($url, 'mixdrop.') !== false) {
            return sprintf(
                '<iframe src="%s" frameborder="0" width="100%%" height="100%%" allow="autoplay; fullscreen; encrypted-media" allowfullscreen referrerpolicy="no-referrer"></iframe>',
                htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
            );
        }

        if (stripos($url, 'krakenfiles.com') !== false) {
            return sprintf(
                '<iframe frameborder="0" src="%s" width="100%%" height="100%%" allow="autoplay; fullscreen; encrypted-media" allowfullscreen referrerpolicy="no-referrer"></iframe>',
                htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
            );
        }

        if (stripos($url, 'acefile.co') !== false) {
            return sprintf(
                '<iframe src="%s" scrolling="no" frameborder="0" width="100%%" height="100%%" allow="autoplay; fullscreen; encrypted-media" allowfullscreen referrerpolicy="no-referrer"></iframe>',
                htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
            );
        }

        // Default iframe wrapper for any URL
        return sprintf(
            '<iframe src="%s" scrolling="no" frameborder="0" width="100%%" height="100%%" allow="autoplay; fullscreen; encrypted-media" allowfullscreen referrerpolicy="no-referrer"></iframe>',
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Check if URL is already an embed/iframe code
     */
    public static function isEmbedCode(string $url): bool
    {
        return stripos($url, '<iframe') === 0;
    }
}
