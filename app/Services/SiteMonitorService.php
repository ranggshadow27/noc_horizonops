<?php

namespace App\Services;

use App\Models\SiteMonitor;
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

        // Inisialisasi array untuk data
        $data1 = [];
        $data2 = [];

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

        // Gabungkan kedua data dan hapus duplikasi berdasarkan terminal_id
        $data = array_merge($data1, $data2);
        $data = array_unique($data, SORT_REGULAR);
        $data = array_values($data); // Reset indeks array
        $totalData = count($data);
        Log::info('Total unique records to process: ' . $totalData);

        // Ambil semua site_id yang ada di SiteMonitor
        $existingSiteIds = SiteMonitor::pluck('site_id')->toArray();
        $successfulUpdates = 0;

        // Proses data dalam batch
        $chunkSize = 100; // Proses 100 data per batch
        collect($data)->chunk($chunkSize)->each(function ($chunk) use ($existingSiteIds, &$successfulUpdates, $totalData) {
            foreach ($chunk as $apiItem) {
                $terminalId = $apiItem['terminal_id'] ?? null;

                // Cek apakah terminal_id ada di SiteMonitor
                if (!$terminalId || !in_array($terminalId, $existingSiteIds)) {
                    Log::warning('Skipping data: terminal_id not found in SiteMonitor or invalid', [
                        'terminal_id' => $terminalId ?? 'Unknown'
                    ]);
                    continue;
                }

                try {
                    // Ambil data berdasarkan site_id
                    $dbData = SiteMonitor::where('site_id', $terminalId)->first();

                    $updateData = [
                        'site_id' => $apiItem['terminal_id'] ?? 'Failed',
                        'modem' => $apiItem['modem'] ?? 'Failed',
                        'mikrotik' => $apiItem['mikrotik'] ?? 'Failed',
                        'ap1' => $apiItem['AP1'] ?? 'Failed',
                        'ap2' => $apiItem['AP2'] ?? 'Failed',
                        'modem_last_up' =>
                        $apiItem['modem'] === 'Down' && !$dbData->modem_last_up ?
                            Carbon::now() : (
                                $apiItem['modem'] !== 'Up' ?
                                $dbData->modem_last_up : null
                            ),
                        'mikrotik_last_up' =>
                        $apiItem['mikrotik'] === 'Down' && !$dbData->mikrotik_last_up ?
                            Carbon::now() : (
                                $apiItem['mikrotik'] !== 'Up' ?
                                $dbData->mikrotik_last_up : null
                            ),
                        'ap1_last_up' =>
                        $apiItem['AP1'] === 'Down' && !$dbData->ap1_last_up ?
                            Carbon::now() : (
                                $apiItem['AP1'] !== 'Up' ?
                                $dbData->ap1_last_up : null
                            ),
                        'ap2_last_up' =>
                        $apiItem['AP2'] === 'Down' && !$dbData->ap2_last_up ?
                            Carbon::now() : (
                                $apiItem['AP2'] !== 'Up' ?
                                $dbData->ap2_last_up : null
                            ),
                    ];

                    // Update atau buat data baru
                    $dbData = SiteMonitor::updateOrCreate(
                        ['site_id' => $terminalId],
                        $updateData
                    );

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
