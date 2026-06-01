<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SweepingTicket;
use App\Models\SiteDetail;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;   // Tambahkan ini

class FetchSweepingTickets extends Command
{
    protected $signature = 'fetch:sweeping-tickets';
    protected $description = 'Fetch data from Apps Script API and insert/update sweeping_tickets table';

    public function handle()
    {
        $apiUrl = 'https://script.google.com/macros/s/AKfycbzb5McYQiskbsMfZDWENTj9PXrNlHEqUV2LOzEshCN_Hdlcm0mNbH6VZp0SYgxun-Syzg/exec';

        try {
            $response = Http::timeout(360)->get($apiUrl);
            if (!$response->successful()) {
                $this->error('Gagal fetch data dari API: ' . $response->status());
                return;
            }

            $apiData = $response->json();
            if (empty($apiData)) {
                $this->info('Tidak ada data dari API.');
                return;
            }

            $validSiteIds = SiteDetail::pluck('site_id')->toArray();
            $processed = 0;
            $created = 0;
            $updated = 0;

            foreach ($apiData as $data) {
                if (!in_array($data['site_id'], $validSiteIds)) {
                    $this->warn("Site ID {$data['site_id']} tidak ditemukan, skipped.");
                    continue;
                }

                $date = \DateTime::createFromFormat('Y-m-d H:i:s', $data['date'] ?? '');
                if (!$date) {
                    $this->warn("Format tanggal invalid: " . ($data['date'] ?? 'null') . ", skipped.");
                    continue;
                }

                $formattedDate = $date->format('ymd'); // YYMMDD

                // ==================== LOGIKA BARU ====================
                // Cek record dengan site_id yang dibuat mulai jam 00:00 hari ini
                $startOfDay = Carbon::parse($date->format('Y-m-d'))->startOfDay();

                $existing = SweepingTicket::where('site_id', $data['site_id'])
                    ->where('created_at', '>=', $startOfDay)
                    ->first();

                if ($existing) {
                    // UPDATE
                    $existing->update([
                        'status'                 => $data['status'],
                        'classification'         => $data['classification'],
                        'problem_classification' => $data['problem_classification'] ?? '-',
                        'cboss_tt'               => $data['cboss_tt'] ?? null,
                        'cboss_problem'          => $data['cboss_tt_problem'] ?? null,
                    ]);
                    $updated++;
                } else {
                    // CREATE baru + generate sweeping_id
                    $countToday = SweepingTicket::where('created_at', '>=', $startOfDay)->count() + 1;

                    $sweepingId = sprintf('SWEEP-%s-%03d', $formattedDate, $countToday);

                    SweepingTicket::create([
                        'sweeping_id'            => $sweepingId,
                        'site_id'                => $data['site_id'],
                        'status'                 => $data['status'],
                        'classification'         => $data['classification'],
                        'problem_classification' => $data['problem_classification'] ?? '-',
                        'cboss_tt'               => $data['cboss_tt'] ?? null,
                        'cboss_problem'          => $data['cboss_tt_problem'] ?? null,
                    ]);
                    $created++;
                }

                $processed++;
            }

            $this->info("✅ Selesai memproses {$processed} data. Created: {$created}, Updated: {$updated}");
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
