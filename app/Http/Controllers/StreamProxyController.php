<?php

namespace App\Http\Controllers;

use App\Models\VideoServer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class StreamProxyController extends Controller
{
    public function redirect(string $token)
    {
        if (!request()->hasValidSignature()) {
            abort(403);
        }

        try {
            $id = Crypt::decryptString($token);
            $server = VideoServer::findOrFail($id);
            // Simple 302 redirect to origin; signed URL hides origin in page markup
            return redirect()->away($server->embed_url);
        } catch (\Throwable $e) {
            abort(404);
        }
    }
}
