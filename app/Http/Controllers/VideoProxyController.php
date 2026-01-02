<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VideoProxyController extends Controller
{
    /**
     * Proxy request ke AnimeSail player internal (154.26.137.28/utils/player/*)
     */
    public function proxyAnimeSail(Request $request)
    {
        $playerType = $request->route('playerType');
        $query = $request->getQueryString();

        if (empty($playerType) || empty($query)) {
            return response('Invalid request', 400);
        }

        // Gunakan HTTPS untuk server AnimeSail/Lokal (karena IP ini support SSL)
        $upstreamUrl = sprintf(
            'https://154.26.137.28/utils/player/%s/?%s',
            urlencode($playerType),
            $query
        );

        try {
            $response = Http::withOptions([
                'verify' => false, // Abaikan sertifikat SSL yang mungkin tidak valid
                'timeout' => 15,
                'allow_redirects' => true,
            ])
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => 'https://google.com'
            ])
            ->get($upstreamUrl);

            return response($response->body(), $response->status())
                ->header('Content-Type', $response->header('Content-Type'))
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type');

        } catch (\Exception $e) {
            Log::error("Video proxy error for {$playerType}: " . $e->getMessage());
            return response('Upstream server error', 502);
        }
    }

    /**
     * Proxy untuk external embed servers (Aghanim, Acefile, dll)
     * Mengatasi masalah HTTP vs HTTPS dan CORS
     */
    public function proxyExternal(Request $request)
    {
        // 1. Ambil URL dan bersihkan
        $url = $request->input('url'); // Pakai input() agar otomatis decode
        
        if (empty($url)) {
            return response('URL is empty', 400);
        }

        // 2. Cek Protokol: Jika tidak ada http/https, tambahkan http:// (Default insecure buat server lama)
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "http://" . $url;
        }

        // 3. Validasi Santai (Jangan pakai FILTER_VALIDATE_URL yang ketat)
        // Selama ada 'http', kita coba fetch saja.
        
        try {
            // 4. Fetch dengan Menyamar jadi Browser (PENTING!)
            $response = Http::withOptions([
                'verify' => false, // Bypass SSL Error
                'timeout' => 20,   // Beri waktu lebih lama
                'allow_redirects' => true
            ])
            ->withHeaders([
                // Header ini wajib supaya Aghanim/Acefile mengira kita manusia, bukan bot
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
            ])
            ->get($url);

            // 5. Kembalikan isi halaman (HTML Iframe) ke browser user
            // Kita juga set header supaya browser user tidak memblokir (CORS)
            return response($response->body(), $response->status())
                ->header('Content-Type', $response->header('Content-Type') ?? 'text/html')
                ->header('Access-Control-Allow-Origin', '*')
                ->header('X-Frame-Options', 'ALLOWALL'); // Izinkan di-iframe

        } catch (\Exception $e) {
            Log::error("Video proxy external error for {$url}: " . $e->getMessage());
            // Tampilkan error di layar hitam agar ketahuan kenapa
            return response("Proxy Error: Gagal mengambil video. " . $e->getMessage(), 502);
        }
    }
}