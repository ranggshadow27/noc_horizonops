<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
    $apiUrl = 'https://api.snt.co.id/v2/api/mhg-rtgs/terminal-data-h47/mhg'; // << GANTI URL API ASLI

    try {
        // Forward request (bisa tambah header kalau butuh)
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            // Tambah kalau butuh auth: 'Authorization' => 'Bearer token_asli',
            // 'Cookie' => 'session=xxx'  // Copy dari Postman kalau perlu
        ])->get($apiUrl);

        if ($response->successful()) {
            return response()->json($response->json()); // Return JSON sama persis
        } else {
            return response()->json(['error' => 'API asli gagal: ' . $response->status()], 500);
        }
    } catch (\Exception $e) {
        return response()->json(['error' => 'Proxy error: ' . $e->getMessage()], 500);
    }
});
