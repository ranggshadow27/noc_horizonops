<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\CheckUpdate;
use App\Models\NmtTickets;
use App\Models\SiteDetail;
use Illuminate\Support\Facades\Http;
use App\Models\TmoData;

class FetchNmtTickets extends Command
{

    protected $signature = 'fetch:nmt-tickets';
    protected $description = 'Ambil data dari API Apps Script dan simpan ke database nmt_tickets';

    public function handle()
    {
        $lastUpdateApiUrl = 'https://script.google.com/macros/s/AKfycbyh7RPiVKhwkPEnjzz2Fkf3e6T3g81I4yQgGd-yCqZSCbSjwTsymytKMzcpy-YUCq5Q2w/exec'; // Ganti dengan API last_update
        // $tmoDataApiUrl = 'https://example.com/api/tmo_data'; // Ganti dengan API data TMO

        // Fetch last_update dari API
        $response = Http::get($lastUpdateApiUrl);
        if ($response->failed()) {
            $this->error('Gagal mengambil data last_update');
            return;
        }

        $apiLastUpdate = Carbon::parse($response->json()['last_update'])
            ->setTimezone('Asia/Jakarta')
            ->format('Y-m-d'); // Sesuaikan key JSON
        // ->format('Y-m-d H:i:s'); // Sesuaikan key JSON

        // Cek last_update di DB
        $dbLastUpdate = CheckUpdate::where('update_name', 'NMT Ticket')->first();

        if ($dbLastUpdate && $dbLastUpdate->update_time === $apiLastUpdate) {
            $this->info('Tidak ada perubahan, fetch dibatalkan.');
            return;
        }

        // Jika berbeda, update last_update di database
        $this->info('Ada perubahan, otw fetch...');

        CheckUpdate::updateOrCreate(
            ['update_name' => 'NMT Ticket'], // Key
            ['update_time' => $apiLastUpdate] // Field yang diperbarui
        );

        // Panggil function untuk fetch dan insert data
        $this->fetchAndInsertNmtTickets();
    }

    private function fetchAndInsertNmtTickets()
    {
        $apiUrl = 'https://script.google.com/macros/s/AKfycbzLIrSJLKpi4zXiTflJNlQaNB0hhj-hXNWN58JZpNwTDZUloZjto8RItot9eAiuEw3tqQ/exec';
        $response = Http::get($apiUrl);

        if ($response->successful()) {
            $data = $response->json();

            foreach ($data as $item) {
                $site = SiteDetail::where('site_id', $item['SITE ID'])->first();

                if (!$site) {
                    $this->info("Data dengan site_id {$item['SITE ID']} di-skip karena tidak ditemukan di site_details.");
                    continue;
                }

                $ticketId = str_replace('/', '-', $item['TICKET ID']);
                $apiStatus = $item['STATUS'];
                $status = ($apiStatus === "CLOSED" && !isset($item['ACTUAL ONLINE'])) ? "OPEN" : $apiStatus;

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
                    'problem_classification' => $item['PROBLEM CLASSIFICATION'],
                    'problem_detail' => $item['DETAIL PROBLEM'],
                    'problem_type' => $item['TEKNIS/NON TEKNIS'],
                    'update_progress' => $item['UPDATE PROGRESS'],
                ];

                if ($existingTicket) {
                    // Update ticket yang sudah ada
                    $updateData = array_merge($ticketData, [
                        'closed_date' => $existingTicket->closed_date // Pertahankan nilai existing jika ada
                    ]);

                    // Logika untuk status CLOSED
                    if ($status === "CLOSED" && isset($item['ACTUAL ONLINE'])) {
                        if ($item['ACTUAL ONLINE'] !== null && $item['ACTUAL ONLINE'] !== "-") {
                            // Hanya update closed_date jika sebelumnya null
                            if ($existingTicket->closed_date === null) {
                                $updateData['closed_date'] = Carbon::parse($item['ACTUAL ONLINE'], 'Asia/Jakarta')
                                    ->format('Y-m-d H:i:s');
                            }
                        }
                    }

                    // Jika status menjadi OPEN, set closed_date ke null
                    if ($status === "OPEN") {
                        $updateData['closed_date'] = null;
                    }

                    $existingTicket->update($updateData);
                    $this->info("Ticket dengan ticket_id {$ticketId} {$item['DATE START TT']} > {$ticketDate} telah diperbarui.");
                } else {
                    // Insert ticket baru
                    if ($status === "CLOSED" && isset($item['ACTUAL ONLINE'])) {
                        if ($item['ACTUAL ONLINE'] !== null && $item['ACTUAL ONLINE'] !== "-") {
                            $ticketData['closed_date'] = Carbon::parse($item['ACTUAL ONLINE'], 'Asia/Jakarta')
                                ->format('Y-m-d H:i:s');
                        }
                    }

                    NmtTickets::create($ticketData);
                    $this->info("Ticket dengan ticket_id {$ticketId} {$item['DATE START TT']} > {$ticketDate} telah ditambahkan.");
                }
            }

            $this->info('Data berhasil diperbarui dari API.');
        } else {
            $this->error('Gagal mengambil data dari API.');
        }
    }
}
