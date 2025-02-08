<?php

namespace App\Services;

use App\Models\SiteMonitor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class SiteMonitorService
{
    public function fetchAndSaveData()
    {
        // URL API pertama
        $url1 = 'https://api.snt.co.id/v2/api/mhg-rtgs/terminal-data-h10/mhg';
        // URL API kedua (ganti sesuai API kedua)
        $url2 = 'https://api.snt.co.id/v2/api/mhg-rtgs/terminal-data-h58/mhg'; // Contoh URL

        // Ambil data dari API pertama
        $response1 = Http::get($url1);
        // Ambil data dari API kedua
        $response2 = Http::get($url2);

        if ($response1->successful() && $response2->successful()) {
            // Ambil data JSON dari API
            $data1 = $response1->json()['data']; // Asumsi 'data' adalah field yang ada
            $data2 = $response2->json()['data'];

            // Gabungkan kedua data
            $data = array_merge($data1, $data2);

            // Proses dan simpan data ke database
            foreach ($data as $item) {
                // Ambil data berdasarkan site_id
                $apiData = SiteMonitor::where('site_id', $item['terminal_id'])->first();

                // Jika data ditemukan, lakukan update, jika tidak buat data baru
                if ($apiData) {
                    $apiData->update([
                        'site_id' => $item['terminal_id'] ?? 'Failed',
                        'modem' => $item['modem'] ?? 'Failed',
                        'mikrotik' => $item['mikrotik'] ?? 'Failed',
                        'ap1' => $item['AP1'] ?? 'Failed',
                        'ap2' => $item['AP2'] ?? 'Failed',

                        'modem_last_up' =>
                        $item['modem'] === 'Down' && !$apiData->modem_last_up ?
                            Carbon::now() : (
                                $item['modem'] !== 'Up' ?
                                $apiData->modem_last_up : null
                            ),

                        'mikrotik_last_up' =>
                        $item['mikrotik'] === 'Down' && !$apiData->mikrotik_last_up ?
                            Carbon::now() : (
                                $item['mikrotik'] !== 'Up' ?
                                $apiData->mikrotik_last_up : null
                            ),

                        'ap1_last_up' =>
                        $item['AP1'] === 'Down' && !$apiData->ap1_last_up ?
                            Carbon::now() : (
                                $item['AP1'] !== 'Up' ?
                                $apiData->ap1_last_up : null
                            ),

                        'ap2_last_up' =>
                        $item['AP2'] === 'Down' && !$apiData->ap2_last_up ?
                            Carbon::now() : (
                                $item['AP2'] !== 'Up' ?
                                $apiData->ap2_last_up : null
                            ),

                    ]);
                } else {
                    // Jika data tidak ada, buat data baru
                    $apiData = SiteMonitor::updateOrCreate([
                        'site_id' => $item['terminal_id'] ?? 'Failed',
                        'modem' => $item['modem'] ?? 'Failed',
                        'mikrotik' => $item['mikrotik'] ?? 'Failed',
                        'ap1' => $item['AP1'] ?? 'Failed',
                        'ap2' => $item['AP2'] ?? 'Failed',
                        'modem_last_up' => $item['modem'] === 'Down' ? Carbon::now() : null,
                        'mikrotik_last_up' => $item['mikrotik'] === 'Down' ? Carbon::now() : null,
                        'ap1_last_up' => $item['AP1'] === 'Down' ? Carbon::now() : null,
                        'ap2_last_up' => $item['AP2'] === 'Down' ? Carbon::now() : null,
                    ]);
                }

                // Update status berdasarkan kondisi 'last_up'
                $this->updateStatus($apiData);
            }
        }
    }

    private function updateStatus(SiteMonitor $apiData)
    {
        $status = 'Normal';

        // Cek apakah salah satu dari modem, mikrotik, ap1, atau ap2 last_up lebih dari 5 hari
        $status = $this->checkStatusBasedOnLastUp($apiData);

        // Update status ke database
        $apiData->update(['status' => $status]);
    }

    private function checkStatusBasedOnLastUp(SiteMonitor $apiData)
    {
        $status = 'Normal';

        // List of fields yang harus diperiksa
        $lastUps = [
            'modem_last_up' => $apiData->modem_last_up,
            'mikrotik_last_up' => $apiData->mikrotik_last_up,
            'ap1_last_up' => $apiData->ap1_last_up,
            'ap2_last_up' => $apiData->ap2_last_up,
        ];

        foreach ($lastUps as $field => $lastUpTime) {
            // Cek jika data last_up tidak null
            if ($lastUpTime !== null) {
                $diffInDays = $lastUpTime->diffInDays(Carbon::now());

                // Periksa status berdasarkan selisih hari
                if ($diffInDays >= 5) {
                    $status = 'Critical';
                } elseif ($diffInDays >= 3 && $diffInDays < 5) {
                    $status = 'Major';
                } elseif ($diffInDays > 1 && $diffInDays < 3) {
                    $status = 'Minor';
                }
            }
        }

        return $status;
    }
}
