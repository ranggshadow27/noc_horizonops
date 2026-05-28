<?php

namespace App\Imports;

use App\Models\CbossTicket;
use App\Models\SiteDetail;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;

class CbossTicketImport implements ToModel, WithStartRow, WithChunkReading
{
    public function startRow(): int
    {
        return 5; // mulai dari baris ke-5
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    private function properCase(string $value = null): ?string
    {
        if (is_null($value) || trim($value) === '') {
            return null;
        }
        return Str::of($value)->lower()->title()->trim();
    }

    private function parseExcelDate($value)
    {
        if (empty($value) || trim($value) === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::createFromTimestampUTC(($value - 25569) * 86400);
            } catch (\Exception $e) {
                Log::warning('Numeric date parse failed: ' . $value);
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            Log::warning('String date parse failed: ' . $value);
            return null;
        }
    }

    private function cleanValue($value)
    {
        if (is_null($value) || $value === '' || strtolower(trim($value)) === 'nan') {
            return null;
        }
        return $value;
    }

    public function model(array $row)
    {
        Log::info('Processing row: ' . json_encode(array_slice($row, 0, 8)));

        if (empty(trim($row[0] ?? '')) && empty(trim($row[1] ?? ''))) {
            return null;
        }

        $ticketId = trim($row[1] ?? ''); // Kolom B = Ticket ID

        if (empty($ticketId) || strtolower($ticketId) === 'nan') {
            return null;
        }

        $mappedRow = [
            'ticket_id'         => $ticketId,
            'subscriber number' => trim($row[5] ?? ''),   // site_id
            'province'          => $this->properCase($this->cleanValue($row[27] ?? null)),
            'spk number'        => $this->cleanValue($row[2] ?? null),
            'problem map'       => $this->cleanValue($row[8] ?? null),
            'trouble category'  => $this->cleanValue($row[18] ?? null),  // sesuaikan index kalau perlu
            'detail action'     => $this->cleanValue($row[9] ?? null),
            'ticket status'     => $this->cleanValue($row[19] ?? null),
            'ticket start'      => $this->cleanValue($row[13] ?? null),
            'ticket end'        => $this->cleanValue($row[14] ?? null),
            'ticket last update' => $this->cleanValue($row[17] ?? null),
        ];

        if (empty($mappedRow['subscriber number'])) {
            return null;
        }

        // Cek SiteDetail
        if (!SiteDetail::where('site_id', $mappedRow['subscriber number'])->exists()) {
            Log::info('SKIP - Site ID not found: ' . $mappedRow['subscriber number']);
            return null;
        }

        $ticketStart     = $this->parseExcelDate($mappedRow['ticket start'])?->format('Y-m-d H:i:s');
        $ticketEnd       = $this->parseExcelDate($mappedRow['ticket end'])?->format('Y-m-d H:i:s');
        $ticketLastUpdate = $this->parseExcelDate($mappedRow['ticket last update'])?->format('Y-m-d H:i:s');

        // Validasi minimal
        $validator = Validator::make($mappedRow, [
            'subscriber number' => 'required|string',
            'ticket_id'         => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed for ticket: ' . $ticketId);
            return null;
        }

        try {
            // Update or Create berdasarkan ticket_id
            return CbossTicket::updateOrCreate(
                ['ticket_id' => $ticketId],
                [
                    'site_id'             => $mappedRow['subscriber number'],
                    'province'            => $mappedRow['province'],
                    'spmk'                => $mappedRow['spk number'],
                    'problem_map'         => $mappedRow['problem map'],
                    'trouble_category'    => $mappedRow['trouble category'],
                    'status'              => $mappedRow['ticket status'],
                    'detail_action'       => $mappedRow['detail action'],
                    'ticket_start'        => $ticketStart,
                    'ticket_end'          => $ticketEnd,
                    'ticket_last_update'  => $ticketLastUpdate,
                    'updated_at'          => now(),
                ]
            );
        } catch (QueryException $e) {
            if ($e->getCode() == '23000' || str_contains($e->getMessage(), 'foreign key constraint')) {
                Log::warning("SKIP - Foreign Key Error for site_id: " . $mappedRow['subscriber number']);
                return null;
            }
            Log::error("DB Error for ticket " . $ticketId . ": " . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            Log::error("General Error for ticket " . $ticketId . ": " . $e->getMessage());
            return null;
        }
    }
}
