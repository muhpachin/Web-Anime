<?php

namespace App\Services;

/**
 * Helper untuk convert URL server video menjadi iframe HTML yang bisa di-embed
 */
class VideoEmbedHelper
{
    /**
     * Convert server URL to embeddable iframe HTML
     */
    public static function toEmbedCode(string $url, ?string $serverName = null): string
    {
        // Already iframe HTML
        if (stripos($url, '<iframe') === 0) {
            return $url;
        }

        $url = trim($url);
        
        // AnimeSail internal players (154.26.137.28/utils/player/*)
        // Convert to local proxy to bypass CORS/firewall
        if (stripos($url, '154.26.137.28') !== false && stripos($url, '/utils/player/') !== false) {
            // Extract player type and query from URL
            if (preg_match('#/utils/player/([^/?]+)/?\?(.*)$#i', $url, $matches)) {
                $playerType = $matches[1]; // blogger, framezilla, gphoto, etc.
                $query = $matches[2];
                $proxyUrl = route('video.proxy.animesail', ['playerType' => $playerType]) . '?' . $query;
                
                return sprintf(
                    '<iframe src="%s" scrolling="no" frameborder="0" width="100%%" height="100%%" allowfullscreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>',
                    htmlspecialchars($proxyUrl, ENT_QUOTES, 'UTF-8')
                );
            }
            
            // Fallback if regex doesn't match
            return sprintf(
                '<iframe src="%s" scrolling="no" frameborder="0" width="100%%" height="100%%" allowfullscreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>',
                htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
            );
        }

        // aghanim.xyz/tools/lokal (lokal player)
        if (stripos($url, 'aghanim.xyz') !== false && stripos($url, '/tools/lokal/') !== false) {
            return sprintf(
                '<iframe src="%s" id="picasa" frameborder="0" width="100%%" height="100%%" allowfullscreen="allowfullscreen" scrolling="no" allow="autoplay; fullscreen; encrypted-media" referrerpolicy="no-referrer"></iframe>',
                htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
            );
        }

        // General iframe sources (MP4Upload, MixDrop, Kraken, etc.) - embed directly, no proxy
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
