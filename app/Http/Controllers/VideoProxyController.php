<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VideoProxyController extends Controller
{
    /**
     * Proxy untuk AnimeSail (Internal IP)
     */
    public function proxyAnimeSail(Request $request)
    {
        $playerType = $request->route('playerType');
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
                'timeout' => 5, // Cukup 5 detik
                'connect_timeout' => 3 
            ])
            ->get($upstreamUrl);

            return response($response->body(), $response->status())
                ->header('Content-Type', $response->header('Content-Type'))
                ->header('Access-Control-Allow-Origin', '*');

        } catch (\Exception $e) {
            // Jangan log terlalu berisik, cukup error umum
            return response("Proxy Error (Internal): " . $e->getMessage(), 502);
        }
    }

    /**
     * Proxy untuk Aghanim, Acefile, dll (External)
     * PERBAIKAN: Timeout lebih ketat & Penanganan Error 521
     */
    public function proxyExternal(Request $request)
    {
        $url = $request->input('url');
        
        if (empty($url)) {
            return response('URL is empty', 400);
        }

        // 1. Pastikan Protokol Ada
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "http://" . $url;
        }

        // 2. Siapkan Penyamaran (Spoofing)
        // Kita parsing URL target untuk membuat header palsu
        $parsed = parse_url($url);
        $scheme = $parsed['scheme'] ?? 'http';
        $host   = $parsed['host'] ?? '';
        
        // Kalau host gagal diparsing, jangan lanjutkan (bisa bikin crash)
        if (empty($host)) {
            return response("Invalid URL Host", 400);
        }

        $fakeOrigin = "$scheme://$host"; 

        try {
            // 3. Request dengan Timeout Ketat (Supaya tidak Error 521)
            $response = Http::withOptions([
                'verify' => false,
                'timeout' => 8,          // Maksimal tunggu 8 detik
                'connect_timeout' => 5,  // Maksimal koneksi 5 detik
                'allow_redirects' => true
            ])
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => $fakeOrigin . '/',
                'Origin' => $fakeOrigin,
                // Hapus header aneh-aneh lain yang berpotensi diblokir
            ])
            ->get($url);

            // 4. Kirim Balik Hasilnya
            return response($response->body(), $response->status())
                ->header('Content-Type', $response->header('Content-Type') ?? 'text/html')
                ->header('Access-Control-Allow-Origin', '*') 
                ->header('X-Frame-Options', 'ALLOWALL');

        } catch (\Exception $e) {
            // Catat error ke log Laravel supaya kita tahu kenapa
            Log::error("Proxy Fail [{$url}]: " . $e->getMessage());
            
            // Tampilkan pesan error sopan di player (bukan 521)
            return response("Maaf, video tidak dapat dimuat lewat proxy. (Timeout/Blocked).", 504);
        }
    }
}