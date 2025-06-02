<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SweepingTicket;
use App\Models\SiteDetail;
use Illuminate\Support\Facades\Http;

class FetchSweepingTickets extends Command
{
    protected $signature = 'fetch:sweeping-tickets';
    protected $description = 'Fetch data from Apps Script API and insert into sweeping_tickets table';

    public function handle()
    {
        // URL API dari Apps Script (ganti dengan URL deploy-mu)
        $apiUrl = 'https://script.google.com/macros/s/AKfycbzb5McYQiskbsMfZDWENTj9PXrNlHEqUV2LOzEshCN_Hdlcm0mNbH6VZp0SYgxun-Syzg/exec';

        try {
            // Fetch data dari API
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

            // Ambil semua site_id dari site_details untuk validasi
            $validSiteIds = SiteDetail::pluck('site_id')->toArray();

            // Proses setiap data dari API
            $count = 0;
            foreach ($apiData as $data) {
                // Cek apakah site_id valid
                if (!in_array($data['site_id'], $validSiteIds)) {
                    $this->warn("Site ID {$data['site_id']} tidak ditemukan di site_details, skipped.");
                    continue;
                }

                // Ambil tanggal dari API dan format ke YYMMDD
                $date = \DateTime::createFromFormat('Y-m-d H:i:s', $data['date']);
                if (!$date) {
                    $this->warn("Format tanggal invalid: {$data['date']}, skipped.");
                    continue;
                }
                $formattedDate = $date->format('ymd');

                // Generate sweeping_id
                $count++;
                $sweepingId = sprintf('SWEEP-%s-%03d', $formattedDate, $count);

                // Insert ke tabel sweeping_tickets
                SweepingTicket::updateOrCreate(
                    ['sweeping_id' => $sweepingId],
                    [
                        'site_id' => $data['site_id'],
                        'status' => $data['status'],
                        'classification' => $data['classification'],
                        'problem_classification' => $data['problem_classification'] ?? '-',
                        'cboss_tt' => $data['cboss_tt'] ?? null,
                        'cboss_problem' => $data['cboss_tt_problem'] ?? null,
                    ]
                );

                // $this->info("Berhasil menambahkan: $sweepingId");
            }

            $this->info("Selesai memproses $count data dari API.");
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
