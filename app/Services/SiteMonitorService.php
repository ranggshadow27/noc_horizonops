<?php

namespace App\Services;

use App\Models\SiteMonitor;
use App\Models\SiteDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SiteMonitorService
{
    public function fetchAndSaveData()
    {
        // GATE KEAMANAN: Validasi Token OSS dulu (cache bersama semua service/command)
        if (!$this->validateOssToken()) {
            Log::error('Token OSS tidak valid atau sudah expired. SiteMonitorService dibatalkan.');
            return;
        }

        Log::info('Token OSS valid. Memulai fetch SiteMonitor data...');

        // URL API
        $url1 = 'https://api.snt.co.id/v2/api/mhg-rtgs/terminal-data-h10/mhg';
        $url2 = 'https://api.snt.co.id/v2/api/mhg-rtgs/terminal-data-h58/mhg';
        $url3 = 'https://api.snt.co.id/v2/api/mhg-rtgs/terminal-data-h47/mhg';

        // Ambil data dari 3 API (hanya 3x HTTP request)
        $data1 = $this->fetchApi($url1, 1);
        $data2 = $this->fetchApi($url2, 2);
        $data3 = $this->fetchApi($url3, 3);

        // Gabungkan dan hapus duplikasi
        $data = array_merge($data1, $data2, $data3);
        $data = array_unique($data, SORT_REGULAR);
        $data = array_values($data);

        // Buat lookup map berdasarkan terminal_id (super cepat)
        $apiDataMap = collect($data)->keyBy('terminal_id')->toArray();

        // Ambil SEMUA site_id dari SiteDetail (sumber utama sekarang)
        $siteIds = SiteDetail::pluck('site_id')->toArray();
        $totalSites = count($siteIds);

        Log::info("Total sites di SiteDetail yang akan diproses: {$totalSites}");

        $successfulUpdates = 0;
        $chunkSize = 100;

        // Proses per batch
        collect($siteIds)->chunk($chunkSize)->each(function ($chunk) use ($apiDataMap, &$successfulUpdates) {
            foreach ($chunk as $terminalId) {
                if (!$terminalId) {
                    continue;
                }

                try {
                    $apiItem = $apiDataMap[$terminalId] ?? null;
                    $dbData  = SiteMonitor::where('site_id', $terminalId)->first();

                    if ($apiItem !== null) {
                        // Normalisasi data dari API
                        $modem     = $this->normalizeStatus($apiItem['modem'] ?? null);
                        $mikrotik  = $this->normalizeStatus($apiItem['mikrotik'] ?? null);
                        $ap1       = $this->normalizeStatus($apiItem['AP1'] ?? null);
                        $ap2       = $this->normalizeStatus($apiItem['AP2'] ?? null);

                        $updateData = [
                            'site_id'          => $terminalId,
                            'sitecode'         => $apiItem['sitecode'] ?? 'Failed',   // sitecode jarang null
                            'modem'            => $modem,
                            'mikrotik'         => $mikrotik,
                            'ap1'              => $ap1,
                            'ap2'              => $ap2,
                            'modem_last_up'    => $this->determineLastUp($modem, $dbData?->modem_last_up),
                            'mikrotik_last_up' => $this->determineLastUp($mikrotik, $dbData?->mikrotik_last_up),
                            'ap1_last_up'      => $this->determineLastUp($ap1, $dbData?->ap1_last_up),
                            'ap2_last_up'      => $this->determineLastUp($ap2, $dbData?->ap2_last_up),
                        ];

                        Log::info('Processed from API', ['site_id' => $terminalId]);
                    } else {
                        // Site TIDAK DITEMUKAN di API → tandai sebagai missing
                        $updateData = [
                            'site_id'          => $terminalId,
                            'sitecode'         => $dbData->sitecode ?? "Not Found",
                            'modem'            => 'Failed',
                            'mikrotik'         => 'Failed',
                            'ap1'              => 'Failed',
                            'ap2'              => 'Failed',
                            'modem_last_up'    => Carbon::parse('1990-01-01 00:00:00'),
                            'mikrotik_last_up' => Carbon::parse('1990-01-01 00:00:00'),
                            'ap1_last_up'      => Carbon::parse('1990-01-01 00:00:00'),
                            'ap2_last_up'      => Carbon::parse('1990-01-01 00:00:00'),
                        ];

                        Log::info('Site tidak ditemukan di API → ditandai missing (1990 last_up)', [
                            'site_id' => $terminalId
                        ]);
                    }

                    // Update atau Create
                    $dbData = SiteMonitor::updateOrCreate(
                        ['site_id' => $terminalId],
                        $updateData
                    );

                    // Log create/update
                    if ($dbData->wasRecentlyCreated) {
                        Log::info('Created new SiteMonitor record', ['site_id' => $terminalId]);
                    } else {
                        Log::info('Updated existing SiteMonitor record', ['site_id' => $terminalId]);
                    }

                    // Update status & sensor_status
                    $this->updateStatus($dbData);
                    $this->updateSensorStatus($dbData);

                    $successfulUpdates++;
                } catch (\Exception $e) {
                    Log::error('Error processing SiteMonitor data', [
                        'site_id' => $terminalId ?? 'Unknown',
                        'error'   => $e->getMessage(),
                        'trace'   => $e->getTraceAsString()
                    ]);
                }
            }
        });

        // Log akhir
        Log::info("Selesai memproses {$successfulUpdates} dari {$totalSites} site di SiteDetail");

        if ($successfulUpdates < $totalSites) {
            Log::warning('Ada site yang gagal diproses', [
                'processed' => $successfulUpdates,
                'total'     => $totalSites,
            ]);
        }

        // Cek data dengan sensor_status null
        $nullSensorStatusCount = SiteMonitor::whereNull('sensor_status')->count();
        if ($nullSensorStatusCount > 0) {
            Log::warning('Found records with null sensor_status', [
                'count' => $nullSensorStatusCount
            ]);
        }
    }

    private function fetchApi(string $url, int $apiNumber): array
    {
        $response = Http::get($url);
        if ($response->successful()) {
            $data = $response->json()['data'] ?? [];
            Log::info("Fetched " . count($data) . " records from API {$apiNumber}");
            return $data;
        }

        Log::error("Failed to fetch data from API {$apiNumber}", [
            'url'    => $url,
            'status' => $response->status(),
            'error'  => $response->body()
        ]);
        return [];
    }

    private function updateStatus(SiteMonitor $dbData)
    {
        $status = 'Normal';

        // Cek apakah salah satu dari modem, mikrotik, ap1, atau ap2 last_up lebih dari 5 hari
        $status = $this->checkStatusBasedOnLastUp($dbData);

        // Update status ke database
        $updated = $dbData->update(['status' => $status]);

        if (!$updated) {
            Log::error('Failed to update status for SiteMonitor', [
                'site_id' => $dbData->site_id,
                'error' => 'Status update operation returned false'
            ]);
        }
    }

    private function normalizeStatus($status)
    {
        if (is_null($status) || $status === "" || $status === "null" || $status === "NULL") {
            return "Failed";
        }

        // Trim dan capitalize pertama (biar konsisten)
        $status = trim($status);
        return ucfirst(strtolower($status));
    }

    private function determineLastUp($currentStatus, $existingLastUp)
    {
        // // Normalisasi dulu: ubah "null", null, empty, dll menjadi string lowercase
        // if (is_null($currentStatus) || $currentStatus === "-" || $currentStatus === "" || $currentStatus === "null" || $currentStatus === "NULL") {
        //     $currentStatus = Carbon::parse('1990-01-01 00:00:00');
        // }
        $formatedStatus = strtolower($currentStatus);

        if ($formatedStatus === "down") {
            return $existingLastUp ?? Carbon::now('Asia/Jakarta');
        }

        if ($formatedStatus === "up") {
            return null;
        }

        // Semua kasus lain (Failed, null, "null", unknown status) → dianggap tidak ada data
        return $existingLastUp ?? Carbon::now('Asia/Jakarta');
    }

    private function validateOssToken(): bool
    {
        $envToken = env('OSS_TOKEN');

        if (!$envToken) {
            Log::warning('OSS_TOKEN tidak ada di .env [SiteMonitorService]');
            return false;
        }

        $cacheKey = 'oss_token_validation';
        $now = Carbon::now('Asia/Jakarta');

        // Cek cache dulu
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if (
                isset($cached['api_token']) &&
                isset($cached['expired_at']) &&
                $cached['api_token'] === $envToken &&
                $now->lessThan(Carbon::parse($cached['expired_at'], 'Asia/Jakarta'))
            ) {
                return true;
            }
        }

        // Ambil dari API kalau cache ga valid
        $url = 'https://script.google.com/macros/s/AKfycbyGv08iyugoWolQlg2AGZzZxooQy3nqd_S1x7n5GOTH0mwlqz-FpbldIuMPp-HJMwKI/exec?app_type=oss_app';

        try {
            $response = Http::timeout(30)->get($url);

            if ($response->failed() || !$response->json()) {
                Log::error('Gagal ambil token OSS [SiteMonitorService]');
                return false;
            }

            $data = $response->json();

            if (!isset($data['token']) || !isset($data['expired'])) {
                Log::error('Format token OSS salah [SiteMonitorService]', $data);
                return false;
            }

            $apiToken = trim($data['token']);
            $expiredAt = Carbon::parse($data['expired'], 'Asia/Jakarta');

            if ($apiToken !== $envToken) {
                Log::warning('Token OSS mismatch! [SiteMonitorService]');
                return false;
            }

            if ($now->greaterThanOrEqualTo($expiredAt)) {
                Log::warning('Token OSS sudah expired! [SiteMonitorService]', [
                    'expired_at' => $expiredAt->format('d-m-Y H:i:s')
                ]);
                return false;
            }

            // Cache dinamis
            $minutesLeft = $now->diffInMinutes($expiredAt, false);
            $cacheMinutes = max(1, min(60, $minutesLeft - 5));

            Cache::put($cacheKey, [
                'api_token' => $apiToken,
                'expired_at' => $expiredAt->toDateTimeString(),
            ], now()->addMinutes($cacheMinutes));

            Log::info("Token OSS valid sampai {$expiredAt->format('d-m-Y H:i:s')} [SiteMonitorService]");
            return true;
        } catch (\Exception $e) {
            Log::error('Exception validasi token OSS [SiteMonitorService]: ' . $e->getMessage());
            return false;
        }
    }

    private function updateSensorStatus(SiteMonitor $dbData)
    {
        // Cek status modem terlebih dahulu
        $modemUp = is_null($dbData->modem_last_up) || ($dbData->modem_last_up && $dbData->modem_last_up->diffInHours(Carbon::now()) < 24);
        $modemDownLong = $dbData->modem_last_up && $dbData->modem_last_up->diffInDays(Carbon::now()) >= 3;

        // Jika modem down lebih dari 3 hari, set sensor_status ke 'All Sensor Down'
        if ($modemDownLong) {
            $sensorStatus = 'All Sensor Down';
        } else {
            // Jika modem online atau down kurang dari 24 jam, cek sensor lainnya
            $routerUp = is_null($dbData->mikrotik_last_up) || ($dbData->mikrotik_last_up && $dbData->mikrotik_last_up->diffInHours(Carbon::now()) < 24);
            $ap1Up = is_null($dbData->ap1_last_up) || ($dbData->ap1_last_up && $dbData->ap1_last_up->diffInHours(Carbon::now()) < 24);
            $ap2Up = is_null($dbData->ap2_last_up) || ($dbData->ap2_last_up && $dbData->ap2_last_up->diffInHours(Carbon::now()) < 24);

            if (!$modemUp) {
                // Jika modem down (kurang dari 3 hari), semua dianggap down
                $sensorStatus = 'All Sensor Down';
            } elseif ($modemUp && !$routerUp) {
                // Jika modem up tapi router down, AP1 dan AP2 otomatis down
                $sensorStatus = 'Router Down';
            } elseif ($modemUp && $routerUp) {
                // Modem dan router up, cek AP1 dan AP2
                if ($ap1Up && $ap2Up) {
                    // Semua up
                    $sensorStatus = 'Online';
                } elseif (!$ap1Up && $ap2Up) {
                    // AP1 down, AP2 up
                    $sensorStatus = 'AP1 Down';
                } elseif ($ap1Up && !$ap2Up) {
                    // AP1 up, AP2 down
                    $sensorStatus = 'AP2 Down';
                } elseif (!$ap1Up && !$ap2Up) {
                    // AP1 dan AP2 down
                    $sensorStatus = 'AP1&2 Down';
                }
            } else {
                // REVISI: Tambah fallback jika logic di atas tidak match (untuk handle kasus baru)
                $sensorStatus = 'Unknown';
            }
        }

        // Update sensor_status ke database
        $updated = $dbData->update(['sensor_status' => $sensorStatus]);

        if (!$updated) {
            Log::error('Failed to update sensor_status for SiteMonitor', [
                'site_id' => $dbData->site_id,
                'error' => 'Sensor status update operation returned false'
            ]);
        }
    }

    private function checkStatusBasedOnLastUp(SiteMonitor $dbData)
    {
        $status = 'Normal';

        // List of fields yang harus diperiksa
        $lastUps = [
            'modem_last_up' => $dbData->modem_last_up,
            'mikrotik_last_up' => $dbData->mikrotik_last_up,
            'ap1_last_up' => $dbData->ap1_last_up,
            'ap2_last_up' => $dbData->ap2_last_up,
        ];

        foreach ($lastUps as $field => $lastUpTime) {
            // Cek jika data last_up tidak null
            if ($lastUpTime !== null) {
                $diffInDays = $lastUpTime->diffInDays(Carbon::now());
                $diffInHours = $lastUpTime->diffInHours(Carbon::now());

                // Periksa status berdasarkan selisih hari
                if ($diffInDays >= 3) {
                    $status = 'Critical';
                } elseif ($diffInHours >= 30 && $diffInDays < 3) {
                    $status = 'Major';
                } elseif ($diffInHours >= 12 && $diffInHours < 30) {
                    $status = 'Minor';
                } elseif ($diffInHours > 6 && $diffInHours < 12) {
                    $status = 'Warning';
                }
            }
        }

        return $status;
    }
}
