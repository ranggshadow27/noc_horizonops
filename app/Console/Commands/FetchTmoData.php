<?php

namespace App\Console\Commands;

use App\Models\CheckUpdate;
use App\Models\SiteDetail;
use App\Models\TmoData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class FetchTmoData extends Command
{
    protected $signature = 'fetch:tmo-data';
    protected $description = 'Ambil data dari API Apps Script dan simpan ke database tmo_data';

    public function handle()
    {
        // GATE KEAMANAN: Validasi Token OSS dulu (cache bersama semua command)
        if (!$this->validateOssToken()) {
            $this->error('Token OSS tidak valid atau sudah expired. Fetch TMO Data dibatalkan.');
            return;
        }

        $this->info('Token OSS valid. Lanjut fetch TMO Data...');

        $lastUpdateApiUrl = 'https://script.google.com/macros/s/AKfycby2OigwtWovjzIF-oAnZZeRXnploV_F5UujtOm-AqmMinvI3I5EMBOskg-a_4inYgPKig/exec'; // Ganti dengan API last_update
        // $tmoDataApiUrl = 'https://example.com/api/tmo_data'; // Ganti dengan API data TMO

        // Fetch last_update dari API
        $response = Http::get($lastUpdateApiUrl);
        if ($response->failed()) {
            $this->error('Gagal mengambil data last_update');
            return;
        }

        $apiLastUpdate = Carbon::parse($response->json()['last_update'])
            ->setTimezone('Asia/Jakarta')
            ->format('Y-m-d H:i:s'); // Sesuaikan key JSON

        // Cek last_update di DB
        $dbLastUpdate = CheckUpdate::where('update_name', 'TMO Data')->first();

        if ($dbLastUpdate && $dbLastUpdate->update_time == $apiLastUpdate) {
            $this->info('Tidak ada perubahan, fetch dibatalkan.');
            return;
        }

        // Jika berbeda, update last_update di database
        $this->info('Ada perubahan, otw fetch...');

        CheckUpdate::updateOrCreate(
            ['update_name' => 'TMO Data'], // Key
            ['update_time' => $apiLastUpdate] // Field yang diperbarui
        );

        // Panggil function untuk fetch dan insert data
        $this->fetchAndInsertTmoData();
    }

    private function validateOssToken(): bool
    {
        $envToken = env('OSS_TOKEN');

        if (!$envToken) {
            Log::warning('OSS_TOKEN tidak ditemukan di .env');
            return false;
        }

        $cacheKey = 'oss_token_validation';
        $now = Carbon::now('Asia/Jakarta');

        // SELALU cache EXACTLY 60 menit — tidak peduli expired di API berapa lama lagi
        $cached = Cache::get($cacheKey);

        if ($cached && isset($cached['checked_at'])) {
            $checkedAt = Carbon::parse($cached['checked_at'], 'Asia/Jakarta');

            // Kalau belum lewat 60 menit dari terakhir cek → langsung pakai cache
            if ($checkedAt->diffInMinutes($now) < 60) {
                // Token dari cache harus sama dengan ENV
                if ($cached['api_token'] === $envToken) {
                    return true;
                }
            }
        }

        // Kalau cache kosong / sudah 60 menit → WAJIB hit API
        $url = 'https://script.google.com/macros/s/AKfycbyGv08iyugoWolQlg2AGZzZxooQy3nqd_S1x7n5GOTH0mwlqz-FpbldIuMPp-HJMwKI/exec?app_type=oss_app';

        try {
            $response = Http::timeout(30)->get($url);

            if ($response->failed() || !$response->json()) {
                Log::error('Gagal ambil token dari OSS API (tidak bisa konek)');
                return false;
            }

            $data = $response->json();

            if (!isset($data['token']) || !isset($data['expired'])) {
                Log::error('Format response token salah', $data);
                return false;
            }

            $apiToken = trim($data['token']);
            $apiExpiredAt = Carbon::parse($data['expired'], 'Asia/Jakarta');

            // Validasi 1: Token harus cocok
            if ($apiToken !== $envToken) {
                Log::warning('Token OSS MISMATCH! API ≠ ENV');
                return false;
            }

            // Validasi 2: Token belum boleh expired
            if ($now->greaterThanOrEqualTo($apiExpiredAt)) {
                Log::warning('Token OSS SUDAH EXPIRED di API!', [
                    'expired_at' => $apiExpiredAt->format('Y-m-d H:i:s')
                ]);
                return false;
            }

            // KALAU LOLOS → Simpan ke cache EXACTLY 60 menit
            Cache::put($cacheKey, [
                'api_token' => $apiToken,
                'checked_at' => $now->toDateTimeString(),
                'expired_at' => $now->addMinutes(60)->toDateTimeString(),
                'expired_at_api' => $apiExpiredAt->toDateTimeString(), // cuma buat info
            ], now()->addMinutes(60)); // FIXED 60 menit!

            Log::info('Token OSS valid! Cache diperbarui untuk 60 menit ke depan.');
            return true;
        } catch (\Exception $e) {
            Log::error('Exception saat cek token OSS: ' . $e->getMessage());
            return false;
        }
    }

    private function fetchAndInsertTmoData()
    {
        $apiUrl = 'https://script.google.com/macros/s/AKfycbxg5FQyVvwbME5A7NZg4r9Xe2QbHFUMXDJ6FlxZJTXwB45ZMZE4VxEMH68y0_p3JTzsRQ/exec'; // Ganti dengan URL API kamu
        $response = Http::get($apiUrl);

        if ($response->successful()) {
            $data = $response->json();

            foreach ($data as $item) {
                $site = SiteDetail::where('site_id', $item['Subscriber Number'])->first();

                if (!$site) {
                    // Skip data jika subscriber_id tidak ditemukan
                    $this->info("Data dengan subscriber_id {$item['Subscriber Number']} di-skip karena tidak ditemukan di site_details.");

                    continue;
                }

                // Convert "problems" string ke array JSON
                $tmoId = str_replace('/', '-', $item['TMO Number']);
                $tmoDate = Carbon::parse($item['TMO Date'])->format('Y-m-d H:i:s');
                $problemsArray = explode(',', $item['Action']);

                // Cek apakah ada "PM" dalam data problems
                $tmoType = collect($problemsArray)->contains(fn($problem) => str_contains($problem, 'PM'))
                    ? 'Preventive Maintenance'
                    : 'Corrective Maintenance';

                TmoData::firstOrCreate(
                    ['tmo_id' => $tmoId], // Sesuaikan kolom unik
                    [
                        'site_id' => $item['Subscriber Number'],
                        'cboss_tmo_code' => $item['TMO Code'],
                        'engineer_name' => $item['KIKO/Technician'],
                        'engineer_number' => $item['KIKO/Technician Phone'],
                        'pic_name' => $item['PIC Location'],
                        'pic_number' => $item['PIC Location Number'],
                        'approval' => 'Approved',
                        'approval_by' => $item['TMO By'],
                        'engineer_note' => $item['Problem'],
                        'spmk_number' => $item['SPK Number'],
                        'tmo_start_date' => $tmoDate,
                        'problem_json' => $problemsArray, // Simpan langsung sebagai array
                        'tmo_type' => $tmoType, // Set TMO type sesuai kondisi
                    ]
                );
            }

            $this->info('Data berhasil diperbarui dari API.');
        } else {
            $this->error('Gagal mengambil data dari API.');
        }
    }
}
