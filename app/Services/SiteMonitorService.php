<?php

namespace App\Services;

use App\Models\SiteMonitor;
use App\Models\SiteDetail; // REVISI: Import model SiteDetail (asumsi nama ini; sesuaikan jika beda)
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SiteMonitorService
{
    public function fetchAndSaveData()
    {
        // URL API pertama
        $url1 = 'https://api.snt.co.id/v2/api/mhg-rtgs/terminal-data-h10/mhg';
        // URL API kedua
        $url2 = 'https://api.snt.co.id/v2/api/mhg-rtgs/terminal-data-h58/mhg';
        // URL API ketiga
        $url3 = 'https://api.snt.co.id/v2/api/mhg-rtgs/terminal-data-h47/mhg';

        // Inisialisasi array untuk data
        $data1 = [];
        $data2 = [];
        $data3 = [];

        // Ambil data dari API pertama
        $response1 = Http::get($url1);
        if ($response1->successful()) {
            $data1 = $response1->json()['data'] ?? [];
            Log::info('Fetched ' . count($data1) . ' records from API 1');
        } else {
            Log::error('Failed to fetch data from API 1', [
                'url' => $url1,
                'status' => $response1->status(),
                'error' => $response1->body()
            ]);
        }

        // Ambil data dari API kedua
        $response2 = Http::get($url2);
        if ($response2->successful()) {
            $data2 = $response2->json()['data'] ?? [];
            Log::info('Fetched ' . count($data2) . ' records from API 2');
        } else {
            Log::error('Failed to fetch data from API 2', [
                'url' => $url2,
                'status' => $response2->status(),
                'error' => $response2->body()
            ]);
        }

        // Ambil data dari API ketiga
        $response3 = Http::get($url3);
        if ($response3->successful()) {
            $data3 = $response3->json()['data'] ?? [];
            Log::info('Fetched ' . count($data3) . ' records from API 3');
        } else {
            Log::error('Failed to fetch data from API 3', [
                'url' => $url3,
                'status' => $response3->status(),
                'error' => $response3->body()
            ]);
        }

        // Gabungkan ketiga data dan hapus duplikasi berdasarkan terminal_id
        $data = array_merge($data1, $data2, $data3);
        $data = array_unique($data, SORT_REGULAR);
        $data = array_values($data); // Reset indeks array
        $totalData = count($data);
        Log::info('Total unique records to process: ' . $totalData);

        // REVISI: Ambil existing site_id dari SiteDetail, bukan SiteMonitor, supaya site_id baru bisa diproses
        $existingSiteIds = SiteDetail::pluck('site_id')->toArray();
        $successfulUpdates = 0;

        // Proses data dalam batch
        $chunkSize = 100; // Proses 100 data per batch
        collect($data)->chunk($chunkSize)->each(function ($chunk) use ($existingSiteIds, &$successfulUpdates, $totalData) {
            foreach ($chunk as $apiItem) {
                $terminalId = $apiItem['terminal_id'] ?? null;

                // REVISI: Cek apakah terminal_id ada di SiteDetail (bukan SiteMonitor)
                if (!$terminalId || !in_array($terminalId, $existingSiteIds)) {
                    Log::warning('Skipping data: terminal_id not found in SiteDetail or invalid', [
                        'terminal_id' => $terminalId ?? 'Unknown'
                    ]);
                    continue;
                }

                try {
                    // REVISI: Ambil data dari SiteMonitor (jika ada), tapi sekarang allow create jika belum ada
                    $dbData = SiteMonitor::where('site_id', $terminalId)->first();

                    $updateData = [
                        'site_id' => $apiItem['terminal_id'] ?? 'Failed',
                        'modem' => $apiItem['modem'] ?? 'Failed',
                        'mikrotik' => $apiItem['mikrotik'] ?? 'Failed',
                        'ap1' => $apiItem['AP1'] ?? 'Failed',
                        'ap2' => $apiItem['AP2'] ?? 'Failed',
                        'modem_last_up' =>
                        $apiItem['modem'] === 'Down' && (!$dbData || !$dbData->modem_last_up) ? // REVISI: Tambah check !$dbData untuk handle create baru
                            Carbon::now() : (
                                $apiItem['modem'] !== 'Up' ?
                                ($dbData ? $dbData->modem_last_up : null) : null
                            ),
                        'mikrotik_last_up' =>
                        $apiItem['mikrotik'] === 'Down' && (!$dbData || !$dbData->mikrotik_last_up) ?
                            Carbon::now() : (
                                $apiItem['mikrotik'] !== 'Up' ?
                                ($dbData ? $dbData->mikrotik_last_up : null) : null
                            ),
                        'ap1_last_up' =>
                        $apiItem['AP1'] === 'Down' && (!$dbData || !$dbData->ap1_last_up) ?
                            Carbon::now() : (
                                $apiItem['AP1'] !== 'Up' ?
                                ($dbData ? $dbData->ap1_last_up : null) : null
                            ),
                        'ap2_last_up' =>
                        $apiItem['AP2'] === 'Down' && (!$dbData || !$dbData->ap2_last_up) ?
                            Carbon::now() : (
                                $apiItem['AP2'] !== 'Up' ?
                                ($dbData ? $dbData->ap2_last_up : null) : null
                            ),
                    ];

                    // Update atau buat data baru
                    $dbData = SiteMonitor::updateOrCreate(
                        ['site_id' => $terminalId],
                        $updateData
                    );

                    // REVISI: Log jika ini create baru
                    if (!$dbData->wasRecentlyCreated) {
                        Log::info('Updated existing SiteMonitor record', ['site_id' => $terminalId]);
                    } else {
                        Log::info('Created new SiteMonitor record from API', ['site_id' => $terminalId]);
                    }

                    // Update status dan sensor_status
                    $this->updateStatus($dbData);
                    $this->updateSensorStatus($dbData);

                    $successfulUpdates++;
                } catch (\Exception $e) {
                    Log::error('Error processing SiteMonitor data', [
                        'site_id' => $terminalId ?? 'Unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
        });

        // Log jumlah data yang berhasil diupdate
        Log::info('Selesai memproses ' . $successfulUpdates . ' dari ' . $totalData . ' data');
        if ($successfulUpdates < $totalData) {
            Log::warning('Not all data were processed successfully', [
                'processed' => $successfulUpdates,
                'total' => $totalData,
                'unprocessed' => $totalData - $successfulUpdates
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
