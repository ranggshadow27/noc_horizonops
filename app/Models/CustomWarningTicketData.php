<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class CustomWarningTicketData extends Model
{
    use Sushi;

    // Primary key
    protected $primaryKey = 'id';

    // Nonaktifkan timestamps
    public $timestamps = false;

    // Data statis via getRows
    public function getRows(): array
    {
        $today = Carbon::today();

            $counts = SweepingTicket::selectRaw('status, COUNT(*) as jumlah')
            ->whereDate('created_at', $today) // Filter hari ini
            ->where('classification', 'WARNING')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => $item->jumlah];
            })->all();

        return [
            ['id' => 1, 'classification' => 'Warning', 'status' => 'Open', 'jumlah' => isset($counts['OPEN']) ? $counts['OPEN'] : 0],
            ['id' => 2, 'classification' => 'Warning', 'status' => 'PIC Tidak Respon', 'jumlah' => isset($counts['PIC TIDAK RESPON']) ? $counts['PIC TIDAK RESPON']: 0],
            ['id' => 3, 'classification' => 'Warning', 'status' => 'Ter Follow Up', 'jumlah' => isset($counts['TER FOLLOW UP']) ? $counts['TER FOLLOW UP']:0],
            ['id' => 4, 'classification' => 'Warning', 'status' => 'Re Follow Up NSO', 'jumlah' => isset($counts['RE FU KE NSO']) ? $counts['RE FU KE NSO'] : 0],
            ['id' => 5, 'classification' => 'Warning', 'status' => 'Closed', 'jumlah' => isset($counts['CLOSED']) ? $counts['CLOSED']: 0],
        ];
    }
}
