<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VideoProxyController extends Controller
{
    /**
     * Proxy request ke AnimeSail player internal (154.26.137.28/utils/player/*)
     * Bypass CORS/firewall issues dengan fetch server-side
     */
    public function proxyAnimeSail(Request $request)
    {
        $playerType = $request->route('playerType'); // blogger, framezilla, gphoto, etc.
        $query = $request->getQueryString();

        if (empty($playerType) || empty($query)) {
            return response('Invalid request', 400);
        }

        $upstreamUrl = sprintf(
            'https://154.26.137.28/utils/player/%s/?%s',
            urlencode($playerType),
            $query
        );

        try {
            $response = Http::withOptions([
                'verify' => false,
                'timeout' => 10,
                'allow_redirects' => true,
            ])->timeout(10)->get($upstreamUrl);

            return response($response->body(), $response->status())
                ->header('Content-Type', $response->header('Content-Type'))
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type');
        } catch (\Exception $e) {
            \Log::error("Video proxy error for {$playerType}: " . $e->getMessage());
            return response('Upstream server error', 502);
        }
    }

    /**
     * Proxy untuk other external embed servers (optional CORS passthrough)
     */
    public function proxyExternal(Request $request)
    {
        $url = $request->query('url');
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return response('Invalid URL', 400);
        }

        try {
            $response = Http::withOptions([
                'verify' => false,
                'timeout' => 10,
            ])->timeout(10)->get($url);

            return response($response->body(), $response->status())
                ->header('Content-Type', $response->header('Content-Type'))
                ->header('Access-Control-Allow-Origin', '*');
        } catch (\Exception $e) {
            \Log::error("Video proxy external error: " . $e->getMessage());
            return response('Upstream server error', 502);
        }
    }
}
