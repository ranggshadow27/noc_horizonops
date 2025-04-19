<?php

namespace App\Imports;

use App\Models\CbossTmo;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CbossTmoImport implements ToModel, WithStartRow
{
    public function startRow(): int
    {
        return 5; // Mulai dari baris ke-5 (data, setelah header di baris ke-4)
    }

    public function model(array $row)
    {
        // Log data row untuk debugging
        Log::info('Processing row: ' . json_encode($row));

        // Cek apakah kolom "No" (index 0) kosong
        if (empty(trim($row[0] ?? ''))) {
            Log::info('Skipping empty row: ' . json_encode($row));
            return null; // Lewati baris jika kolom "No" kosong
        }

        // Mapping kolom berdasarkan index (sesuaikan dengan urutan di Excel)
        $mappedRow = [
            'tmo number' => $row[1] ?? null, // Kolom B: TMO Number
            'subscriber number' => $row[7] ?? null, // Kolom G: Subscriber Number
            'province' => $row[26] ?? null, // Kolom Y: Province
            'spk number' => $row[12] ?? null, // Kolom L: SPK Number
            'kiko/technician' => $row[14] ?? null, // Kolom N: KIKO/Technician
            'kiko/technician phone' => $row[15] ?? null, // Kolom O: KIKO/Technician Phone
            'pic location' => $row[27] ?? null, // Kolom Z: PIC Location
            'pic location number' => $row[28] ?? null, // Kolom AA: PIC Location Number
            'tmo by' => $row[18] ?? null, // Kolom R: TMO By
            'tmo code' => $row[2] ?? null, // Kolom C: TMO Code
            'es no' => $row[21] ?? null, // Kolom U: Es No
            'eb no' => $row[20] ?? null, // Kolom T: EB No
            'ifl cable' => $row[17] ?? null, // Kolom Q: IFL Cable
            'problem' => $row[22] ?? null, // Kolom V: Problem
            'action' => $row[23] ?? null, // Kolom W: Action
            'homebase name' => $row[23] ?? null, // Kolom AE: Homebase Name
            'tmo date' => $row[19] ?? null, // Kolom S: TMO Date
        ];

        // dd($mappedRow);

        // Validasi data
        $validator = Validator::make($mappedRow, [
            'tmo number' => 'required|string',
            'subscriber number' => 'required|string',
            'province' => 'required|string',
            'spk number' => 'nullable|string',
            'kiko/technician' => 'nullable|string',
            'kiko/technician phone' => 'nullable|string',
            'pic location' => 'nullable|string',
            'pic location number' => 'nullable|string',
            'tmo by' => 'nullable|string',
            'tmo code' => 'nullable|string',
            'es no' => 'nullable|string',
            'eb no' => 'nullable|string',
            'ifl cable' => 'nullable|string',
            'problem' => 'nullable|string',
            'action' => 'nullable|string',
            'homebase name' => 'nullable|string',
            'tmo date' => 'required|date',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed for row: ' . json_encode($mappedRow) . ' Errors: ' . json_encode($validator->errors()));
            throw new \Exception('Invalid data in row: ' . json_encode($validator->errors()));
        }

        // Konversi Action ke JSON
        $actions = !empty($mappedRow['action']) && $mappedRow['action'] !== '-' ? explode(',', $mappedRow['action']) : [];
        $actionJson = json_encode(array_map('trim', $actions));

        // Konversi TMO Date ke format datetime
        $tmoDate = Carbon::parse($mappedRow['tmo date'])->format('Y-m-d H:i:s');

        return new CbossTmo([
            'tmo_id' => $mappedRow['tmo number'],
            'site_id' => $mappedRow['subscriber number'],
            'province' => $mappedRow['province'],
            'spmk_number' => $mappedRow['spk number'],
            'techinican_name' => $mappedRow['kiko/technician'],
            'techinican_number' => $mappedRow['kiko/technician phone'],
            'pic_name' => $mappedRow['pic location'],
            'pic_number' => $mappedRow['pic location number'],
            'tmo_by' => $mappedRow['tmo by'],
            'tmo_code' => $mappedRow['tmo code'],
            'esno' => $mappedRow['es no'],
            'sqf' => $mappedRow['eb no'],
            'ifl_cable' => $mappedRow['ifl cable'],
            'problem' => $mappedRow['problem'] === '-' ? null : $mappedRow['problem'],
            'action' => $actionJson,
            'homebase' => $mappedRow['homebase name'],
            'tmo_date' => $tmoDate,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}