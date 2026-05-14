<?php

namespace App\Imports;

use App\Models\CbossTmo;
use App\Models\SiteDetail;        // Tambahkan ini
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CbossTmoImport implements ToModel, WithStartRow
{
    public function startRow(): int
    {
        return 5;
    }

    private function properCase(string $value = null): ?string
    {
        if (is_null($value) || trim($value) === '') {
            return $value;
        }

        return Str::of($value)->lower()->title()->trim();
    }

    public function model(array $row)
    {
        Log::info('Processing row: ' . json_encode($row));

        // Skip jika kolom No kosong
        if (empty(trim($row[0] ?? ''))) {
            Log::info('Skipping empty row');
            return null;
        }

        $siteId = trim($row[7] ?? '');

        // Skip jika site_id kosong
        if (empty($siteId)) {
            Log::warning('Skipping row with empty site_id');
            return null;
        }

        // Mapping data
        $mappedRow = [
            'tmo number'           => $row[1] ?? null,
            'subscriber number'    => $siteId,                    // site_id
            'province'             => $this->properCase($row[26] ?? null),
            'spk number'           => $row[12] ?? null,
            'st number'            => $row[13] ?? null,
            'kiko/technician'      => $this->properCase($row[14] ?? null),
            'kiko/technician phone' => $row[15] ?? null,
            'pic location'         => $this->properCase($row[27] ?? null),
            'pic location number'  => $row[28] ?? null,
            'tmo by'               => $this->properCase($row[18] ?? null),
            'tmo code'             => $row[2] ?? null,
            'es no'                => $row[21] ?? null,
            'eb no'                => $row[20] ?? null,
            'ifl cable'            => $row[17] ?? null,
            'problem'              => $row[22] ?? null,
            'action'               => $row[23] ?? null,
            'homebase name'        => $this->properCase($row[32] ?? null),
            'tmo date'             => $row[19] ?? null,
            // Data tambahan untuk SiteDetail
            'site_name'            => $row[8] ?? null,
            'address'              => $row[10] ?? null,
            'latitude'             => $row[25] ?? null,
            'longitude'            => $row[24] ?? null,
        ];

        // Validasi
        $validator = Validator::make($mappedRow, [
            'tmo number'        => 'required|string',
            'subscriber number' => 'required|string',
            'province'          => 'required|string',
            'tmo date'          => 'required|date',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed: ' . json_encode($validator->errors()));
            throw new \Exception('Invalid data: ' . json_encode($validator->errors()));
        }

        // === LOGIC BARU: Handle Site Detail ===
        $siteExists = SiteDetail::where('site_id', $siteId)->exists();

        if (!$siteExists) {
            if (strlen($siteId) < 15) {
                Log::warning("Site ID {$siteId} (<15 chars) not found in site_details. Skipping row.");
                return null; // Skip row
            }

            // Buat Site Detail baru jika panjang >= 15
            Log::info("Creating new site_detail for site_id: {$siteId}");

            SiteDetail::updateOrCreate(
                ['site_id' => $siteId],
                [
                    'site_name'         => $mappedRow['site_name'],
                    'province'          => $mappedRow['province'],
                    'administrative_area' => '-',
                    'address'           => $mappedRow['address'],
                    'latitude'          => $mappedRow['latitude'],
                    'longitude'         => $mappedRow['longitude'],
                    'spotbeam'          => '-',
                    'ip_hub'            => '-',
                    'gateway'           => '-',
                    'power_source'      => '-',
                    'batch'             => '-',
                    'pic_number'        => $mappedRow['pic location number'],
                    'pic_name'          => $mappedRow['pic location'],
                    'installer_name'    => $mappedRow['kiko/technician'],
                    'installer_number'  => $mappedRow['kiko/technician phone'],
                ]
            );
        }

        // === Insert/Update CbossTmo ===
        $actions = !empty($mappedRow['action']) && $mappedRow['action'] !== '-'
            ? explode(',', $mappedRow['action'])
            : [];

        $spkNumber = $mappedRow['spk number'] ?: $mappedRow['st number'];

        $tmoDate = Carbon::parse($mappedRow['tmo date'])->format('Y-m-d H:i:s');

        return CbossTmo::updateOrCreate(
            ['tmo_id' => $mappedRow['tmo number']],
            [
                'site_id'          => $siteId,
                'province'         => $mappedRow['province'],
                'spmk_number'      => $spkNumber,
                'techinican_name'  => $mappedRow['kiko/technician'],
                'techinican_number' => $mappedRow['kiko/technician phone'],
                'pic_name'         => $mappedRow['pic location'],
                'pic_number'       => $mappedRow['pic location number'],
                'tmo_by'           => $mappedRow['tmo by'],
                'tmo_code'         => $mappedRow['tmo code'],
                'esno'             => $mappedRow['es no'],
                'sqf'              => $mappedRow['eb no'],
                'ifl_cable'        => $mappedRow['ifl cable'],
                'problem'          => $mappedRow['problem'] === '-' ? null : $mappedRow['problem'],
                'action'           => $actions,
                'homebase'         => $mappedRow['homebase name'],
                'tmo_date'         => $tmoDate,
                'updated_at'       => now(),
            ]
        );
    }
}
