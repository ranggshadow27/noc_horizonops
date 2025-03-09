<?php

namespace App\Console\Commands;

use App\Models\CheckUpdate;
use App\Models\SiteDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\TmoData;
use Carbon\Carbon;

class FetchTmoData extends Command
{
    protected $signature = 'fetch:tmo-data';
    protected $description = 'Ambil data dari API Apps Script dan simpan ke database tmo_data';

    public function handle()
    {
        $lastUpdateApiUrl = 'https://script.google.com/macros/s/AKfycby2OigwtWovjzIF-oAnZZeRXnploV_F5UujtOm-AqmMinvI3I5EMBOskg-a_4inYgPKig/exec'; // Ganti dengan API last_update
        // $tmoDataApiUrl = 'https://example.com/api/tmo_data'; // Ganti dengan API data TMO

        // Fetch last_update dari API
        $response = Http::get($lastUpdateApiUrl);
        if ($response->failed()) {
            $this->error('Gagal mengambil data last_update');
            return;
        }

        $apiLastUpdate = Carbon::parse($response->json()['last_update'])
            ->setTimezone('Asia/Jakarta')
            ->format('Y-m-d H:i:s'); // Sesuaikan key JSON

        // Cek last_update di DB
        $dbLastUpdate = CheckUpdate::where('update_name', 'TMO Data')->first();

        if ($dbLastUpdate && $dbLastUpdate->update_time == $apiLastUpdate) {
            $this->info('Tidak ada perubahan, fetch dibatalkan.');
            return;
        }

        // Jika berbeda, update last_update di database
        $this->info('Ada perubahan, otw fetch...');

        CheckUpdate::updateOrCreate(
            ['update_name' => 'TMO Data'], // Key
            ['update_time' => $apiLastUpdate] // Field yang diperbarui
        );

        // Panggil function untuk fetch dan insert data
        $this->fetchAndInsertTmoData();
    }

    private function fetchAndInsertTmoData()
    {
        $apiUrl = 'https://script.google.com/macros/s/AKfycbxg5FQyVvwbME5A7NZg4r9Xe2QbHFUMXDJ6FlxZJTXwB45ZMZE4VxEMH68y0_p3JTzsRQ/exec'; // Ganti dengan URL API kamu
        $response = Http::get($apiUrl);

        if ($response->successful()) {
            $data = $response->json();

            foreach ($data as $item) {
                $site = SiteDetail::where('site_id', $item['Subscriber Number'])->first();

                if (!$site) {
                    // Skip data jika subscriber_id tidak ditemukan
                    $this->info("Data dengan subscriber_id {$item['Subscriber Number']} di-skip karena tidak ditemukan di site_details.");

                    continue;
                }

                // Convert "problems" string ke array JSON
                $tmoId = str_replace('/', '-', $item['TMO Number']);
                $tmoDate = Carbon::parse($item['TMO Date'])->format('Y-m-d H:i:s');
                $problemsArray = explode(',', $item['Action']);

                // Cek apakah ada "PM" dalam data problems
                $tmoType = collect($problemsArray)->contains(fn($problem) => str_contains($problem, 'PM'))
                    ? 'Preventive Maintenance'
                    : 'Corrective Maintenance';

                TmoData::firstOrCreate(
                    ['tmo_id' => $tmoId], // Sesuaikan kolom unik
                    [
                        'site_id' => $item['Subscriber Number'],
                        'cboss_tmo_code' => $item['TMO Code'],
                        'engineer_name' => $item['KIKO/Technician'],
                        'engineer_number' => $item['KIKO/Technician Phone'],
                        'pic_name' => $item['PIC Location'],
                        'pic_number' => $item['PIC Location Number'],
                        'approval' => 'Approved',
                        'approval_by' => $item['TMO By'],
                        'engineer_note' => $item['Problem'],
                        'spmk_number' => $item['SPK Number'],
                        'tmo_start_date' => $tmoDate,
                        'problem_json' => $problemsArray, // Simpan langsung sebagai array
                        'tmo_type' => $tmoType, // Set TMO type sesuai kondisi
                    ]
                );
            }

            $this->info('Data berhasil diperbarui dari API.');
        } else {
            $this->error('Gagal mengambil data dari API.');
        }
    }
}
