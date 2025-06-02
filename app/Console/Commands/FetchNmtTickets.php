<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\CheckUpdate;
use App\Models\NmtTickets;
use App\Models\SiteDetail;
use Illuminate\Support\Facades\Http;
use App\Models\TmoData;
use Illuminate\Support\Facades\Log;

class FetchNmtTickets extends Command
{
    protected $signature = 'fetch:nmt-tickets';
    protected $description = 'Ambil data dari API Apps Script dan simpan ke database nmt_tickets';

    public function handle()
    {
        $lastUpdateApiUrl = 'https://script.google.com/macros/s/AKfycbyh7RPiVKhwkPEnjzz2Fkf3e6T3g81I4yQgGd-yCqZSCbSjwTsymytKMzcpy-YUCq5Q2w/exec'; // Ganti dengan API last_update

        // Fetch last_update dari API
        $response = Http::get($lastUpdateApiUrl);
        if ($response->failed()) {
            $this->error('Gagal mengambil data last_update');
            return;
        }

        $apiLastUpdate = Carbon::parse($response->json()['last_update'])
            ->setTimezone('Asia/Jakarta')
            ->format('Y-m-d'); // Sesuaikan key JSON

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
        $apiUrl = 'https://script.google.com/macros/s/AKfycbzbhQSS07lD8FSN68pKDtwFjfIaxKgfjQarPWLgvFV6Bu7aOEldxPOVTUgNHunfQ1IclA/exec';
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
                    ->orWhere('actual_online', '>=', Carbon::today('Asia/Jakarta')->startOfDay());
            })->pluck('ticket_id')->toArray();

            // Identify OPEN tickets in DB but not in API
            $ticketsToClose = array_diff($dbTicketIds, $apiTicketIds);

            // Log::info("Ini datanya apiTicketIds: " . print_r($apiTicketIds, true));

            // Log::info("Ini datanya dbTicketIds: " . print_r($dbTicketIds, true));

            // Log::info("Ini datanya ticketsToClose: " . print_r($ticketsToClose, true));

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

                    // Log::info("Ticket dengan ticket_id {$ticketId} tidak ditemukan di API, status diubah menjadi CLOSED pada {$yesterdayDate}.");
                    $this->info("Ticket dengan ticket_id {$ticketId} tidak ditemukan di API, status diubah menjadi CLOSED pada {$yesterdayDate}.");
                }
            }

            // Process API data as per existing logic
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

                    $existingTicket->update($updateData);

                    // Log::info("Ticket dengan ticket_id {$ticketId} {$item['DATE START TT']} > {$ticketDate} telah diperbarui.");
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

                    // Log::info("Ticket dengan ticket_id {$ticketId} {$item['DATE START TT']} > {$ticketDate} telah ditambahkan.");
                    $this->info("Ticket dengan ticket_id {$ticketId} {$item['DATE START TT']} > {$ticketDate} telah ditambahkan.");
                }
            }

            $this->info('Data berhasil diperbarui dari API.');
        } else {
            $this->error('Gagal mengambil data dari API.');
        }
    }
}
