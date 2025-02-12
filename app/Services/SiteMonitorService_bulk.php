<?php

namespace App\Services;

use App\Models\SiteDetail;
use App\Models\SiteMonitor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class SiteMonitorService
{
    public function fetchAndSaveData()
    {
        $url1 = 'https://api.snt.co.id/v2/api/mhg-rtgs/terminal-data-h10/mhg';
        $url2 = 'https://api.snt.co.id/v2/api/mhg-rtgs/terminal-data-h58/mhg';

        $response1 = Http::get($url1);
        $response2 = Http::get($url2);

        if ($response1->successful()) {
            $data1 = $response1->json()['data'];
            // $data2 = $response2->json()['data'];

            $data = array_merge($data1);

            // Ambil site_id dari API
            $siteIds = array_column($data, 'terminal_id');

            // Ambil data lama dari database
            $existingData = SiteMonitor::whereIn('site_id', $siteIds)->get()->keyBy('site_id');

            // Ambil site_id yang valid dari `site_details`
            $validSiteIds = SiteDetail::pluck('site_id')->toArray();

            $bulkUpdate = [];

            foreach ($data as $item) {
                $siteId = $item['terminal_id'] ?? null;
                $siteCode = $item['sitecode'] ?? 'Failed';

                // Lewati jika site_id tidak ada di site_details
                if (!in_array($siteId, $validSiteIds)) {
                    continue;
                }

                $existing = $existingData[$siteId] ?? null;

                $modem_last_up = $existing?->modem_last_up;
                $mikrotik_last_up = $existing?->mikrotik_last_up;
                $ap1_last_up = $existing?->ap1_last_up;
                $ap2_last_up = $existing?->ap2_last_up;

                // **Conditional update modem_last_up**
                if ($item['modem'] === 'Up') {
                    $modem_last_up = null;
                } elseif ($item['modem'] === 'Down' && !$modem_last_up) {
                    $modem_last_up = Carbon::now();
                }

                // **Conditional update mikrotik_last_up**
                if ($item['mikrotik'] === 'Up') {
                    $mikrotik_last_up = null;
                } elseif ($item['mikrotik'] === 'Down' && !$mikrotik_last_up) {
                    $mikrotik_last_up = Carbon::now();
                }

                // **Conditional update AP1**
                if ($item['AP1'] === 'Up') {
                    $ap1_last_up = null;
                } elseif ($item['AP1'] === 'Down' && !$ap1_last_up) {
                    $ap1_last_up = Carbon::now();
                }

                // **Conditional update AP2**
                if ($item['AP2'] === 'Up') {
                    $ap2_last_up = null;
                } elseif ($item['AP2'] === 'Down' && !$ap2_last_up) {
                    $ap2_last_up = Carbon::now();
                }

                $status = 'Normal';

                // Ambil semua last_up yang ada nilainya
                $lastUps = array_filter([
                    $modem_last_up,
                    $mikrotik_last_up,
                    $ap1_last_up,
                    $ap2_last_up
                ]);

                if (!empty($lastUps)) {
                    // Hitung selisih hari dari yang paling lama
                    $maxDays = max(array_map(fn($time) => Carbon::parse($time)->diffInDays(), $lastUps));

                    if ($maxDays >= 5) {
                        $status = 'Critical';
                    } elseif ($maxDays >= 3) {
                        $status = 'Major';
                    } elseif ($maxDays >= 1) {
                        $status = 'Minor';
                    }
                }

                $bulkUpdate[] = [
                    'site_id' => $siteId,
                    'sitecode' => $siteCode,
                    'modem' => $item['modem'] ?? 'Failed',
                    'mikrotik' => $item['mikrotik'] ?? 'Failed',
                    'ap1' => $item['AP1'] ?? 'Failed',
                    'ap2' => $item['AP2'] ?? 'Failed',
                    'modem_last_up' => $modem_last_up,
                    'mikrotik_last_up' => $mikrotik_last_up,
                    'ap1_last_up' => $ap1_last_up,
                    'ap2_last_up' => $ap2_last_up,
                    'status' => $status,
                    'updated_at' => now(),
                ];
            }

            // Bulk insert atau update dalam sekali query
            if (!empty($bulkUpdate)) {
                SiteMonitor::upsert(
                    $bulkUpdate,
                    ['site_id'], // Primary key untuk upsert (tidak mengubah site_id)
                    ['sitecode', 'modem', 'mikrotik', 'ap1', 'ap2', 'modem_last_up', 'mikrotik_last_up', 'ap1_last_up', 'ap2_last_up', 'status', 'updated_at']
                );
            }

            $this->updateStatusBulk();
        }
    }


    private function updateStatusBulk()
    {
        // Ambil semua data SiteMonitor
        $siteMonitors = SiteMonitor::all();

        $updates = [];

        foreach ($siteMonitors as $site) {
            $status = 'Normal';

            // Ambil semua last_up yang ada nilainya
            $lastUps = array_filter([
                $site->modem_last_up,
                $site->mikrotik_last_up,
                $site->ap1_last_up,
                $site->ap2_last_up
            ]);

            if (!empty($lastUps)) {
                // Hitung selisih hari dari yang paling lama
                $maxDays = max(array_map(fn($time) => Carbon::parse($time)->diffInDays(), $lastUps));

                if ($maxDays >= 5) {
                    $status = 'Critical';
                } elseif ($maxDays >= 3) {
                    $status = 'Major';
                } elseif ($maxDays >= 1) {
                    $status = 'Minor';
                }
            }

            // Siapkan data untuk bulk update
            $updates[] = [
                'site_id' => $site->site_id,
                'sitecode' => $site->sitecode,
                'status' => $status,
                'updated_at' => now(),
            ];
        }

        // Eksekusi bulk update hanya jika ada perubahan
        if (!empty($updates)) {
            SiteMonitor::upsert($updates, ['site_id'], ['sitecode', 'status', 'updated_at']);
        }
    }
}
