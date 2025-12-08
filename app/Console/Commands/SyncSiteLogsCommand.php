<?php

namespace App\Console\Commands;

use App\Models\SiteLog;
use App\Models\SiteMonitor;
use App\Models\NmtTickets;
use App\Models\SiteDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SyncSiteLogsCommand extends Command
{
    protected $signature = 'site:sync-logs';
    protected $description = 'Sync site logs every 10 minutes based on SiteMonitor and NmtTickets data';

    public function handle()
    {
        // GATE KEAMANAN: Cek token OSS dulu (cache 1 jam)
        if (!$this->validateOssToken()) {
            $this->error('Token OSS tidak valid atau sudah expired. Sync site logs dibatalkan.');
            return;
        }

        $this->info('Token OSS valid. Melanjutkan sync site logs...');

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

    private function validateOssToken(): bool
    {
        $envToken = env('OSS_TOKEN');

        if (!$envToken) {
            Log::warning('OSS_TOKEN tidak ditemukan di .env [SyncSiteLogsCommand]');
            return false;
        }

        $cacheKey = 'oss_token_validation'; // Sama dengan command lain → cache dibagi
        $now = Carbon::now('Asia/Jakarta');

        // Cek cache dulu (1 jam atau sampai mendekati expired)
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);

            if (
                isset($cached['api_token']) &&
                isset($cached['expired_at']) &&
                $cached['api_token'] === $envToken
            ) {
                $expiredAt = Carbon::parse($cached['expired_at'], 'Asia/Jakarta');

                if ($now->lessThan($expiredAt)) {
                    return true;
                }
            }
        }

        // Kalau cache ga ada / invalid → ambil dari API
        $url = 'https://script.google.com/macros/s/AKfycbyGv08iyugoWolQlg2AGZzZxooQy3nqd_S1x7n5GOTH0mwlqz-FpbldIuMPp-HJMwKI/exec?app_type=oss_app';

        try {
            $response = Http::timeout(30)->get($url);

            if ($response->failed() || !$response->json()) {
                Log::error('Gagal ambil token OSS di SyncSiteLogsCommand');
                return false;
            }

            $data = $response->json();

            if (!isset($data['token']) || !isset($data['expired'])) {
                Log::error('Format response token OSS salah', $data);
                return false;
            }

            $apiToken = trim($data['token']);
            $expiredAt = Carbon::parse($data['expired'], 'Asia/Jakarta');

            if ($apiToken !== $envToken) {
                Log::warning("Token OSS mismatch di SyncSiteLogsCommand! ENV ≠ API");
                return false;
            }

            if ($now->greaterThanOrEqualTo($expiredAt)) {
                Log::warning("Token OSS sudah expired pada: " . $expiredAt->format('d-m-Y H:i:s'));
                return false;
            }

            // Cache ulang (dengan durasi dinamis)
            $minutesUntilExpire = $now->diffInMinutes($expiredAt, false);
            $cacheMinutes = max(1, min(60, $minutesUntilExpire - 5)); // max 1 jam, minimal 1 menit, buffer 5 menit

            Cache::put($cacheKey, [
                'api_token' => $apiToken,
                'expired_at' => $expiredAt->toDateTimeString(),
            ], now()->addMinutes($cacheMinutes));

            return true;
        } catch (\Exception $e) {
            Log::error('Exception saat validasi token OSS [SyncSiteLogsCommand]: ' . $e->getMessage());
            return false;
        }
    }
}
