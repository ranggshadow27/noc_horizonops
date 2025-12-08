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
}
