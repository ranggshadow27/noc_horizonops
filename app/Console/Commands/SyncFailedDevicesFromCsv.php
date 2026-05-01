<?php

namespace App\Console\Commands;

use App\Models\SiteMonitor;
use App\Models\SiteMonitorCsv;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncFailedDevicesFromCsv extends Command
{
    protected $signature = 'sync:failed-devices';
    protected $description = 'Sync Failed devices (modem, mikrotik, ap1, ap2) dari SiteMonitorCsv ke SiteMonitor';

    public function handle()
    {
        $this->info('Starting sync failed devices...');

        // Ambil semua site yang punya setidaknya satu device Failed di SiteMonitor
        $failedSites = SiteMonitor::where(function ($query) {
            $query->where('modem', 'Failed')
                ->orWhere('mikrotik', 'Failed')
                ->orWhere('ap1', 'Failed')
                ->orWhere('ap2', 'Failed');
        })->get([
            'site_id',
            'modem',
            'mikrotik',
            'ap1',
            'ap2',
        ]);

        $this->info("failedSites : " . json_encode($failedSites));


        $count = 0;

        foreach ($failedSites as $site) {
            $csv = SiteMonitorCsv::where('site_id', $site->site_id)->first();

            if (!$csv) {
                Log::warning("SiteMonitorCsv not found for site_id: {$site->site_id}");
                continue;
            }

            $updateData = [];

            // Cek satu per satu device yang Failed, lalu ambil data dari CSV
            if ($site->modem === 'Failed') {
                $updateData['modem'] = $csv->modem;
                $updateData['modem_last_up'] = $csv->modem_last_up; // sesuaikan nama kolom
            }
            if ($site->mikrotik === 'Failed') {
                $updateData['mikrotik'] = $csv->mikrotik;
                $updateData['mikrotik_last_up'] = $csv->mikrotik_last_up;
            }
            if ($site->ap1 === 'Failed') {
                $updateData['ap1'] = $csv->ap1;
                $updateData['ap1_last_up'] = $csv->ap1_last_up;
            }
            if ($site->ap2 === 'Failed') {
                $updateData['ap2'] = $csv->ap2;
                $updateData['ap2_last_up'] = $csv->ap2_last_up;
            }

            $this->info("Data : " . json_encode($updateData));

            // Update kolom umum
            $updateData['status'] = $csv->status;
            $updateData['sensor_status'] = $csv->sensor_status;
            // tambahkan kolom lain yang ingin di-sync


            if (!empty($updateData)) {
                SiteMonitor::where('site_id', $site->site_id)
                    ->update($updateData);

                $count++;
                $this->info("Updated site_id: {$site->site_id}");
            }
        }

        $this->info("Sync completed. {$count} sites updated.");
        Log::info("SyncFailedDevices completed - {$count} sites updated.");

        return 0;
    }
}
