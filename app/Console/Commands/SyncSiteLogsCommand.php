<?php

namespace App\Console\Commands;

use App\Models\SiteLog;
use App\Models\SiteMonitor;
use App\Models\NmtTickets;
use App\Models\SiteDetail;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SyncSiteLogsCommand extends Command
{
    protected $signature = 'site:sync-logs';
    protected $description = 'Sync site logs every 10 minutes based on SiteMonitor and NmtTickets data';

    public function handle()
    {
        // Ambil semua site_id dari SiteDetails
        SiteDetail::chunk(100, function ($sites) {
            foreach ($sites as $site) {
                // Generate site_log_id: site_id + tanggal hari ini (Ymd)
                $today = Carbon::today()->format('Ymd');
                $siteLogId = "{$site->site_id}-{$today}";

                // Cari atau buat record SiteLog untuk hari ini
                $siteLog = SiteLog::firstOrCreate(
                    ['site_log_id' => $siteLogId],
                    [
                        'site_id' => $site->site_id,
                        'modem_uptime' => 0,
                        'traffic_uptime' => 0,
                        'modem_last_up' => null,
                        'sensor_status' => null,
                        'nmt_ticket' => null,
                    ]
                );

                // Ambil data SiteMonitor untuk site ini
                $siteMonitor = SiteMonitor::where('site_id', $site->site_id)->first();

                if ($siteMonitor) {
                    // Update modem_uptime (max 6)
                    if (!$siteMonitor->modem_last_up && $siteLog->modem_uptime < 6) {
                        $siteLog->modem_uptime += 1;
                    }

                    // Update modem_last_up
                    $siteLog->modem_last_up = $siteMonitor->modem_last_up;

                    // Update traffic_uptime (max 6)
                    if (in_array($siteMonitor->sensor_status, ['Online', 'AP1 Down', 'AP2 Down']) && $siteLog->traffic_uptime < 6) {
                        $siteLog->traffic_uptime += 1;
                    }

                    // Update sensor_status
                    $siteLog->sensor_status = $siteMonitor->sensor_status;
                }

                // Ambil nmt_ticket untuk site_id ini
                $ticket = NmtTickets::where('site_id', $site->site_id)
                    ->where(function ($query) {
                        $query->where('status', 'OPEN')
                            ->orWhere('created_at', '>=', Carbon::today()->startOfDay());
                    })
                    ->latest('created_at')
                    ->first();

                $siteLog->nmt_ticket = $ticket ? $ticket->ticket_id : '-';

                // Simpan perubahan
                $siteLog->save();
            }
        });

        $this->info('Site logs synced successfully!');
    }
}
