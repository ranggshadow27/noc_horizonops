<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CheckUpdate;
use App\Models\NmtTickets;
use App\Models\SiteDetail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FetchNmtTickets extends Command
{
    protected $signature = 'fetch:nmt-tickets';
    protected $description = 'Ambil data dari API Apps Script dan simpan ke database nmt_tickets';

    public function handle()
    {
        // Langkah 1: Validasi Token OSS dengan cache 1 jam
        if (!$this->validateOssToken()) {
            $this->error('Token OSS tidak valid atau sudah expired. Fetch dibatalkan.');
            return;
        }

        $this->info('Token OSS valid. Lanjut cek update...');

        // Langkah 2: Cek last_update
        $lastUpdateApiUrl = 'https://script.google.com/macros/s/AKfycbyh7RPiVKhwkPEnjzz2Fkf3e6T3g81I4yQgGd-yCqZSCbSjwTsymytKMzcpy-YUCq5Q2w/exec';

        $response = Http::get($lastUpdateApiUrl);
        if ($response->failed()) {
            $this->error('Gagal mengambil data last_update');
            return;
        }

        $apiLastUpdate = Carbon::parse($response->json()['last_update'])
            ->setTimezone('Asia/Jakarta')
            ->format('Y-m-d');

        $dbLastUpdate = CheckUpdate::where('update_name', 'NMT Ticket')->first();

        if ($dbLastUpdate && $dbLastUpdate->update_time === $apiLastUpdate) {
            $this->info('Tidak ada perubahan, fetch dibatalkan.');
            return;
        }

        $this->info('Ada perubahan, otw fetch NMT Tickets...');

        CheckUpdate::updateOrCreate(
            ['update_name' => 'NMT Ticket'],
            ['update_time' => $apiLastUpdate]
        );

        $this->fetchAndInsertNmtTickets();
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

        // Cek cache dulu (valid 1 jam)
        $cached = Cache::get($cacheKey);

        if ($cached && isset($cached['api_token']) && isset($cached['expired_at'])) {
            $cachedExpiredAt = Carbon::parse($cached['expired_at'], 'Asia/Jakarta');

            // Kalau token dari cache masih sama dengan ENV dan belum expired
            if ($cached['api_token'] === $envToken && $now->lessThan($cachedExpiredAt)) {
                $this->info("Token dari cache valid sampai: " . $cachedExpiredAt->format('d M Y H:i:s'));
                return true;
            }

            // Kalau token di ENV berubah (misal diganti manual), tetap invalid
            if ($cached['api_token'] !== $envToken) {
                Log::warning('Token di .env berubah! Cache dibuang.');
            }
        }

        // Kalau tidak ada cache / sudah kadaluarsa → ambil dari API
        $tokenApiUrl = 'https://script.google.com/macros/s/AKfycbyGv08iyugoWolQlg2AGZzZxooQy3nqd_S1x7n5GOTH0mwlqz-FpbldIuMPp-HJMwKI/exec?app_type=oss_app';

        try {
            $response = Http::timeout(30)->get($tokenApiUrl);

            if ($response->failed() || !$response->json()) {
                Log::error('Gagal mengambil data token dari OSS API');
                return false;
            }

            $data = $response->json();

            if (!isset($data['token']) || !isset($data['expired'])) {
                Log::error('Response token tidak lengkap', $data);
                return false;
            }

            $apiToken = trim($data['token']);
            $expiredAt = Carbon::parse($data['expired'], 'Asia/Jakarta');

            // Validasi token cocok
            if ($apiToken !== $envToken) {
                Log::warning("Token mismatch! ENV: {$envToken} | API: {$apiToken}");
                return false;
            }

            // Validasi belum expired
            if ($now->greaterThanOrEqualTo($expiredAt)) {
                Log::warning("Token sudah expired sejak: " . $expiredAt->format('Y-m-d H:i:s'));
                return false;
            }

            // Simpan ke cache selama 1 jam (atau sampai 5 menit sebelum expired, mana yang lebih kecil)
            $cacheMinutes = $now->diffInMinutes($expiredAt, false) - 5; // 5 menit sebelum expired
            $cacheMinutes = $cacheMinutes > 60 ? 60 : ($cacheMinutes > 0 ? $cacheMinutes : 1);

            Cache::put($cacheKey, [
                'api_token' => $apiToken,
                'expired_at' => $expiredAt->toDateTimeString(),
                'checked_at' => $now->toDateTimeString()
            ], now()->addMinutes($cacheMinutes));

            $this->info("Token valid & dicache sampai: " . $expiredAt->format('d M Y H:i:s'));
            return true;
        } catch (\Exception $e) {
            Log::error('Error validasi token OSS: ' . $e->getMessage());
            return false;
        }
    }

    private function fetchAndInsertNmtTickets()
    {
        try {
            $apiUrl = 'https://script.google.com/macros/s/AKfycbykPw72mtXfQbemS-WFUa-3TFzFPmWnrIgqDshBghoKuXU99ktFslpnvmn_GtMla8aVjA/exec';
            $response = Http::timeout(360)->get($apiUrl);

            if ($response->successful()) {
                $data = $response->json();

                // Collect all ticket_ids from API response
                $apiTicketIds = collect($data)->pluck('TICKET ID')->map(function ($ticketId) {
                    return str_replace('/', '-', $ticketId);
                })->toArray();

                // Fetch only ticket_ids with status OPEN from database
                $dbTicketIds = NmtTickets::where(function ($query) {
                    $query->where('status', 'OPEN')
                        ->orWhere('closed_date', '>=', Carbon::today('Asia/Jakarta')->startOfDay());
                })->pluck('ticket_id')->toArray();

                // Identify OPEN tickets in DB but not in API
                $ticketsToClose = array_diff($dbTicketIds, $apiTicketIds);

                // Update OPEN tickets not in API to CLOSED
                foreach ($ticketsToClose as $ticketId) {
                    $ticket = NmtTickets::where('ticket_id', $ticketId)->first();

                    if ($ticket) {
                        $yesterdayDate = Carbon::now('Asia/Jakarta')
                            ->subDay()
                            ->startOfDay()
                            ->translatedFormat('Y-m-d H:i:s');

                        $ticket->update([
                            'status' => 'CLOSED',
                            'closed_date' => $yesterdayDate,
                        ]);
                    }
                }

                // Process API data
                foreach ($data as $item) {
                    $site = SiteDetail::where('site_id', $item['SITE ID'])->first();

                    if (!$site) {
                        continue;
                    }

                    $ticketId = str_replace('/', '-', $item['TICKET ID']);
                    $apiStatus = $item['STATUS'];
                    $status = ($apiStatus === "CLOSED" && !isset($item['ACTUAL ONLINE'])) ? "OPEN" : $apiStatus;
                    $targetOnline = (!isset($item['TARGET ONLINE'])) ? null : $item['TARGET ONLINE'];

                    $ticketDate = Carbon::parse($item['DATE START TT'], 'Asia/Jakarta')
                        ->addDay()
                        ->startOfDay()
                        ->translatedFormat('Y-m-d H:i:s');

                    $existingTicket = NmtTickets::where('ticket_id', $ticketId)->first();

                    // Data dasar yang akan digunakan untuk update atau create
                    $ticketData = [
                        'ticket_id' => $ticketId,
                        'site_id' => $item['SITE ID'],
                        'status' => $status,
                        'date_start' => $ticketDate,
                        'aging' => $item['DOWN TIME'],
                        'target_online' => $targetOnline,
                        'problem_classification' => $item['PROBLEM CLASSIFICATION'],
                        'problem_detail' => $item['DETAIL PROBLEM'],
                        'problem_type' => $item['TEKNIS/NON TEKNIS'],
                        'update_progress' => $item['UPDATE PROGRESS'],
                    ];

                    // Tambahkan cboss_tt jika ada di API
                    if (isset($item['CBOSS TT']) && !empty($item['CBOSS TT'])) {
                        $ticketData['cboss_tt'] = $item['CBOSS TT'];
                    }

                    if ($existingTicket) {
                        // Update ticket yang sudah ada
                        $updateData = array_merge($ticketData, [
                            'closed_date' => $existingTicket->closed_date // Pertahankan nilai existing jika ada
                        ]);

                        // Logika untuk status CLOSED
                        if ($status === "CLOSED" && isset($item['ACTUAL ONLINE'])) {
                            if ($item['ACTUAL ONLINE'] !== null && $item['ACTUAL ONLINE'] !== "-") {
                                $updateData['closed_date'] = Carbon::parse($item['ACTUAL ONLINE'], 'Asia/Jakarta')
                                    ->format('Y-m-d H:i:s');
                            }
                        }

                        // Jika status menjadi OPEN, set closed_date ke null
                        if ($status === "OPEN") {
                            $updateData['closed_date'] = null;
                        }

                        // Update hanya jika cboss_tt dari API ada, jika tidak, pertahankan nilai existing
                        if (!isset($item['CBOSS TT']) || empty($item['CBOSS TT'])) {
                            $updateData['cboss_tt'] = $existingTicket->cboss_tt;
                        }

                        $existingTicket->update($updateData);
                    } else {
                        // Insert ticket baru
                        if ($status === "CLOSED" && isset($item['ACTUAL ONLINE'])) {
                            if ($item['ACTUAL ONLINE'] !== null && $item['ACTUAL ONLINE'] !== "-") {
                                $ticketData['closed_date'] = Carbon::parse($item['ACTUAL ONLINE'], 'Asia/Jakarta')
                                    ->format('Y-m-d H:i:s');
                            }
                        }

                        NmtTickets::create($ticketData);
                    }
                }

                $this->info('Data berhasil diperbarui dari API.');
            }
        } catch (\Exception $e) {
            $this->error('Gagal mengambil data dari API. Err : ' . $e->getMessage());
        }
    }
}
