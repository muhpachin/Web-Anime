<?php

namespace App\Http\Controllers;

use App\Models\VideoServer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;

class VideoSourceController extends Controller
{
    /**
     * Get video source URL (encrypted)
     */
    public function getSource(Request $request)
    {
        $serverId = $request->input('server');
        
        if (!$serverId) {
            return response()->json(['error' => 'Invalid request'], 400);
        }
        
        try {
            // Decrypt server ID
            $decryptedId = Crypt::decryptString($serverId);
            
            // Get video server
            $videoServer = VideoServer::findOrFail($decryptedId);
            
            $embedUrl = $videoServer->embed_url;
            $type = $this->getVideoType($embedUrl);

            // Generate short-lived signed URL that maps to internal stream proxy
            $signedUrl = URL::temporarySignedRoute(
                'stream.proxy',
                now()->addMinutes(5),
                ['token' => Crypt::encryptString($videoServer->id)]
            );
            
            // For iframe/embed, we still return the proxied player page
            if ($type === 'html' || $type === 'url') {
                $playerUrl = URL::temporarySignedRoute(
                    'player.proxy',
                    now()->addMinutes(5),
                    ['token' => Crypt::encryptString($videoServer->id)]
                );
                return response()->json([
                    'url' => $playerUrl,
                    'type' => 'iframe',
                    'proxied' => true,
                ]);
            }

            return response()->json([
                'url' => $signedUrl,
                'type' => $type,
                'proxied' => true,
                'expires_in' => 300,
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Not found'], 404);
        }
    }
    
    private function getVideoType($url)
    {
        if (str_contains($url, '<iframe')) {
            return 'iframe';
        } elseif (str_ends_with(strtolower($url), '.mp4')) {
            return 'mp4';
        } elseif (str_ends_with(strtolower($url), '.m3u8')) {
            return 'm3u8';
        }
        return 'url';
    }
}
