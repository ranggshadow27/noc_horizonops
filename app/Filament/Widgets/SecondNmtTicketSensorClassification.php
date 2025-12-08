<?php

namespace App\Filament\Widgets;

use App\Models\NmtTickets;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SecondNmtTicketSensorClassification extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';
    protected static bool $deferLoading = true;

    protected function getStats(): array
    {
        // Inisialisasi counter
        $ap1DownCount = 0;
        $ap2DownCount = 0;
        $ap1And2DownCount = 0;

        // Ambil semua tiket dengan relasi siteMonitor
        $tickets = NmtTickets::with('siteMonitor')
            ->where(function ($query) {
                $query->where('status', 'OPEN')
                    ->orWhere(function ($query) {
                        $query->where('status', 'CLOSED')
                            ->whereDate('closed_date', Carbon::today());
                    });
            })
            ->get();

        foreach ($tickets as $record) {
            $siteMonitor = $record->siteMonitor;

            // Ambil semua waktu yang tidak null
            $times = [];
            if ($siteMonitor->modem_last_up) {
                $times['modem'] = Carbon::parse($siteMonitor->modem_last_up);
            }
            if ($siteMonitor->mikrotik_last_up) {
                $times['router'] = Carbon::parse($siteMonitor->mikrotik_last_up);
            }
            if ($siteMonitor->ap1_last_up) {
                $times['ap1'] = Carbon::parse($siteMonitor->ap1_last_up);
            }
            if ($siteMonitor->ap2_last_up) {
                $times['ap2'] = Carbon::parse($siteMonitor->ap2_last_up);
            }

            // Jika ada waktu, cek status
            if (!empty($times)) {
                // Ambil waktu paling lama (datetime terkecil)
                $earliest = null;
                $earliestKey = null;
                foreach ($times as $key => $time) {
                    if (is_null($earliest) || $time->lt($earliest)) {
                        $earliest = $time;
                        $earliestKey = $key;
                    }
                }

                // Tentukan status berdasarkan prioritas
                if ($earliestKey === 'ap1' && isset($times['ap2']) && $times['ap1']->equalTo($times['ap2'])) {
                    $ap1And2DownCount++;
                } elseif ($earliestKey === 'ap1') {
                    $ap1DownCount++;
                } elseif ($earliestKey === 'ap2') {
                    $ap2DownCount++;
                }
            }
        }

        return [
            Stat::make('AP1 Down Tickets', $ap1DownCount)
                ->description('Access Point 1 offline')
                ->color('danger'),
            Stat::make('AP2 Down Tickets', $ap2DownCount)
                ->description('Access Point 2 offline')
                ->color('danger'),
            Stat::make('AP1&2 Down Tickets', $ap1And2DownCount)
                ->description('Both Access Points offline')
                ->color('danger'),
        ];
    }
}
