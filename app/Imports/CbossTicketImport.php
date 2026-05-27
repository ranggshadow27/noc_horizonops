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
        return 5;
    }

    public function chunkSize(): int
    {
        return 1000;
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

    public function model(array $row)
    {
        Log::info('Processing row: ' . json_encode(array_slice($row, 0, 8)));

        if (empty(trim($row[0] ?? '')) && empty(trim($row[1] ?? ''))) {
            return null;
        }

        $mappedRow = [
            'subscriber number' => trim($row[5] ?? ''),
            'province'          => $this->properCase($row[27] ?? null),
            'spk number'        => $row[2] ?? null,
            'problem map'       => $row[8] ?? null,
            'trouble category'  => $row[18] ?? null,
            'detail action'     => $row[9] ?? null,
            'ticket status'     => $row[19] ?? null,
            'ticket start'      => $row[13] ?? null,
            'ticket end'        => $row[14] ?? null,
            'ticket last update' => $row[17] ?? null,
        ];

        if (empty($mappedRow['subscriber number'])) {
            return null;
        }

        // Cek SiteDetail dulu
        if (!SiteDetail::where('site_id', $mappedRow['subscriber number'])->exists()) {
            Log::info('SKIP - Site ID not found: ' . $mappedRow['subscriber number']);
            return null;
        }

        $formatTicketStart   = $this->parseExcelDate($mappedRow['ticket start']);
        $formatTicketEnd     = $this->parseExcelDate($mappedRow['ticket end']);
        $formatTicketLastUpdate = $this->parseExcelDate($mappedRow['ticket last update']);

        $ticketStart     = $formatTicketStart?->format('Y-m-d H:i:s');
        $ticketEnd       = $formatTicketEnd?->format('Y-m-d H:i:s');
        $ticketLastUpdate = $formatTicketLastUpdate?->format('Y-m-d H:i:s');

        // Validasi
        $validator = Validator::make($mappedRow, [
            'subscriber number' => 'required|string',
            'province'          => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed for site: ' . $mappedRow['subscriber number']);
            return null;
        }

        try {
            $existingTicket = $ticketStart
                ? CbossTicket::where('ticket_start', $ticketStart)
                ->where('site_id', $mappedRow['subscriber number'])
                ->first()
                : null;

            if ($existingTicket && strtolower($existingTicket->status ?? '') === 'closed') {
                return null;
            }

            $ticketId = $existingTicket ? $existingTicket->ticket_id : $this->generateTicketId();

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
            // Tangkap error Foreign Key Constraint
            if ($e->getCode() == '23000' || str_contains($e->getMessage(), 'foreign key constraint')) {
                Log::warning("SKIP - Foreign Key Error for site_id: " . $mappedRow['subscriber number'] . " | " . $e->getMessage());
                return null;
            }

            // Error lain tetap di-throw
            Log::error("Unexpected DB Error: " . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            Log::error("General Error for site " . $mappedRow['subscriber number'] . ": " . $e->getMessage());
            return null;
        }
    }
}
