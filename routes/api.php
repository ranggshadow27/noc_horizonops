<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/proxy-status', function (Request $request) {
    // === 1. CEK API KEY (WAJIB!) ===
    $apiKey = $request->header('X-API-KEY');
    $validKey = 'ratipray_zabb1x742_secret_2025'; // << GANTI DENGAN KEY RAHASIA KAMU!

    if ($apiKey !== $validKey) {
        Log::warning('Unauthorized access to proxy-status', ['ip' => $request->ip()]);
        return response()->json(['error' => 'Invalid API Key'], 401);
    }

    // === 2. RATE LIMIT: Max 10 request per menit per IP ===
    $rateKey = 'proxy-status:' . $request->ip();
    if (RateLimiter::tooManyAttempts($rateKey, 10)) {
        Log::warning('Rate limit exceeded', ['ip' => $request->ip()]);
        return response()->json(['error' => 'Too many requests. Coba lagi nanti.'], 429);
    }
    RateLimiter::hit($rateKey, 60); // 60 detik

    // === 3. (OPSIONAL) IP WHITELIST - Google Apps Script IP Range ===
    // Google IP range: https://www.gstatic.com/ipranges/goog.json
    // Tapi ribet, skip dulu kalau ga mau

    // === 4. CACHE 30 DETIK (Hemat request ke API asli) ===
    $apiUrl = 'https://api.snt.co.id/v2/api/mhg-rtgs/terminal-data-h47/mhg'; // << GANTI
    $cacheKey = 'zabbix_status_data';

    $data = Cache::remember($cacheKey, 30, function () use ($apiUrl) {
        try {
            $response = Http::timeout(10)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                // Tambah auth kalau API asli butuh:
                // 'Authorization' => 'Bearer xxx',
                // 'Cookie' => 'session=abc123'
            ])->get($apiUrl);

            if ($response->successful()) {
                return $response->json();
            }
            Log::error('API asli gagal', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Proxy error', ['message' => $e->getMessage()]);
            return null;
        }
    });

    // === 5. KALAU GAGAL, KASIH PESAN ===
    if (!$data) {
        return response()->json(['error' => 'Gagal ambil data dari server'], 502);
    }

    // === 6. LOG SUKSES ===
    Log::info('Proxy-status accessed', ['ip' => $request->ip(), 'count' => count($data)]);

    // === 7. BALIKIN DATA ===
    return response()->json($data)->header('Access-Control-Allow-Origin', '*');
})->name('proxy-status');
