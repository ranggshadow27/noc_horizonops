<?php

namespace App\Imports;

use App\Models\CbossTicket;
use App\Models\SiteDetail; // Add this to query SiteDetail
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class CbossTicketImport implements ToModel, WithStartRow, WithChunkReading
{
    public function startRow(): int
    {
        return 5;
    }

    public function chunkSize(): int
    {
        return 1000; // Process 1000 rows at a time
    }

    private function properCase(string $value = null): ?string
    {
        if (is_null($value) || trim($value) === '') {
            return $value;
        }

        return Str::of($value)->lower()->title()->trim();
    }

    private function generateTicketId(): string
    {
        $lastTicket = CbossTicket::orderBy('ticket_id', 'desc')->first();

        if (!$lastTicket) {
            return 'TT00001';
        }

        $lastNumber = (int) substr($lastTicket->ticket_id, 2);
        $newNumber = $lastNumber + 1;

        return 'TT' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    public function model(array $row)
    {
        Log::info('ZZ Processing row: ' . json_encode($row));

        if (empty(trim($row[0] ?? ''))) {
            Log::info('Skipping empty row: ' . json_encode($row));
            return null;
        }

        // Mapping kolom, abaikan ticket number dari Excel
        $mappedRow = [
            'subscriber number' => $row[5] ?? null,
            'province' => $this->properCase($row[26] ?? null),
            'spk number' => $row[3] ?? null,
            'problem map' => $row[9] ?? null,
            'trouble category' => $row[21] ?? null,
            'detail action' => $row[10] ?? null,
            'ticket status' => $row[22] ?? null,
            'ticket start' => $row[13] ?? null,
            'ticket end' => $row[16] ?? null,
            'ticket last update' => $row[20] ?? null,
        ];

        // Check if subscriber number exists in SiteDetail
        if (!SiteDetail::where('site_id', $mappedRow['subscriber number'])->exists()) {
            Log::info('Skipping row: Subscriber number ' . $mappedRow['subscriber number'] . ' not found in SiteDetail');
            return null;
        }

        // Konversi tanggal dari Excel serial number
        $formatTicketEnd = $mappedRow['ticket end'] ? Carbon::createFromTimestampUTC(($mappedRow['ticket end'] - 25569) * 86400) : null;
        $formatTicketStart = $mappedRow['ticket start'] ? Carbon::createFromTimestampUTC(($mappedRow['ticket start'] - 25569) * 86400) : null;
        $formatTicketLastUpdate = $mappedRow['ticket last update'] ? Carbon::createFromTimestampUTC(($mappedRow['ticket last update'] - 25569) * 86400) : null;

        $ticketEnd = $formatTicketEnd ? $formatTicketEnd->format('Y-m-d H:i:s') : null;
        $ticketStart = $formatTicketStart ? $formatTicketStart->format('Y-m-d H:i:s') : null;
        $ticketLastUpdate = $formatTicketLastUpdate ? $formatTicketLastUpdate->format('Y-m-d H:i:s') : null;

        // dd("Ini Datanya Timezone: {$formatTicketStart} - {$ticketStart}" . $mappedRow['subscriber number'] . $mappedRow['ticket start']) . Carbon::now()->timezoneName;

        // Log::info('Ticket End Raw: ' . $mappedRow['ticket start']);
        // Log::info("Ticket End Converted: {$formatTicketStart}");
        // Log::info("Ticket End: {$ticketStart}");

        // Validasi data
        $validator = Validator::make($mappedRow, [
            'subscriber number' => 'required|string',
            'province' => 'required|string',
            'spk number' => 'nullable|string',
            'trouble category' => 'nullable|string',
            'detail action' => 'nullable|string',
            'problem map' => 'nullable|string',
            'ticket status' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed for row: ' . json_encode($mappedRow) . ' Errors: ' . json_encode($validator->errors()));
            throw new \Exception('Invalid data in row: ' . json_encode($validator->errors()));
        }

        // Cek apakah ticket_start sudah ada di database
        $existingTicket = $ticketStart ? CbossTicket::where('ticket_start', $ticketStart)->first() : null;

        // Jika tiket sudah ada dan statusnya "Closed", skip update
        if ($existingTicket && strtolower($existingTicket->status) === 'closed') {
            return null;
        }

        // Tentukan ticket_id: gunakan yang sudah ada atau generate baru
        $ticketId = $existingTicket ? $existingTicket->ticket_id : $this->generateTicketId();

        return CbossTicket::updateOrCreate(
            ['ticket_id' => $ticketId],
            [
                'site_id' => $mappedRow['subscriber number'],
                'province' => $mappedRow['province'],
                'spmk' => $mappedRow['spk number'],
                'problem_map' => $mappedRow['problem map'],
                'trouble_category' => $mappedRow['trouble category'],
                'status' => $mappedRow['ticket status'],
                'detail_action' => $mappedRow['detail action'],
                'ticket_start' => $ticketStart,
                'ticket_end' => $ticketEnd,
                'ticket_last_update' => $ticketLastUpdate,
                'updated_at' => now(),
            ]
        );
    }
}
