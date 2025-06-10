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
        } else {
            Log::error('Failed to fetch data from API 2', [
                'url' => $url2,
                'status' => $response2->status(),
                'error' => $response2->body()
            ]);
        }

        // Gabungkan kedua data
        $data = array_merge($data1, $data2);
        $totalData = count($data);
        $successfulUpdates = 0;

        // Proses dan simpan data ke database
        foreach ($data as $apiItem) {
            try {
                // Ambil data berdasarkan site_id
                $dbData = SiteMonitor::where('site_id', $apiItem['terminal_id'])->first();

                // Jika data ditemukan, lakukan update, jika tidak buat data baru
                if ($dbData) {
                    $updated = $dbData->update([
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
                    ]);

                    if ($updated) {
                        $successfulUpdates++;
                    } else {
                        Log::error('Failed to update SiteMonitor', [
                            'site_id' => $apiItem['terminal_id'] ?? 'Unknown',
                            'error' => 'Update operation returned false'
                        ]);
                    }
                } else {
                    // Jika data tidak ada, buat data baru
                    $dbData = SiteMonitor::updateOrCreate([
                        'site_id' => $apiItem['terminal_id'] ?? 'Failed',
                    ], [
                        'sitecode' => $apiItem['sitecode'] ?? 'Failed',
                        'modem' => $apiItem['modem'] ?? 'Failed',
                        'mikrotik' => $apiItem['mikrotik'] ?? 'Failed',
                        'ap1' => $apiItem['AP1'] ?? 'Failed',
                        'ap2' => $apiItem['AP2'] ?? 'Failed',
                        'modem_last_up' =>
                        $apiItem['modem'] === 'Down' ? Carbon::now() : null,
                        'mikrotik_last_up' =>
                        $apiItem['mikrotik'] === 'Down' ? Carbon::now() : null,
                        'ap1_last_up' =>
                        $apiItem['AP1'] === 'Down' ? Carbon::now() : null,
                        'ap2_last_up' =>
                        $apiItem['AP2'] === 'Down' ? Carbon::now() : null,
                    ]);
                    $successfulUpdates++;
                }

                // Update status berdasarkan kondisi 'last_up'
                $this->updateStatus($dbData);
            } catch (\Exception $e) {
                Log::error('Error processing SiteMonitor data', [
                    'site_id' => $apiItem['terminal_id'] ?? 'Unknown',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        // Log jumlah data yang berhasil diupdate
        Log::info('Selesai memproses ' . $successfulUpdates . ' dari ' . $totalData . ' data');
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
