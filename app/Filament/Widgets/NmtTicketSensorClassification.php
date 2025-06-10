<?php

namespace App\Filament\Widgets;

use App\Models\NmtTickets;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class NmtTicketSensorClassification extends BaseWidget
{
    protected static string $view = 'filament.widgets.sensor-status-widget';

    // protected int | string | array $columnSpan = 6;

    public function getData(): array
    {
        // Inisialisasi counter
        $onlineCount = 0;
        $allSensorDownCount = 0;
        $routerDownCount = 0;
        $ap1DownCount = 0;
        $ap2DownCount = 0;
        $ap1And2DownCount = 0;

        // Ambil tiket dengan status OPEN atau CLOSED dengan closed_date hari ini
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

            // Jika tidak ada siteMonitor atau semua null, anggap Online
            if (!$siteMonitor || (
                is_null($siteMonitor->modem_last_up) &&
                is_null($siteMonitor->mikrotik_last_up) &&
                is_null($siteMonitor->ap1_last_up) &&
                is_null($siteMonitor->ap2_last_up)
            )) {
                $onlineCount++;
                continue;
            }

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
                $uniqueTimes = array_unique(array_map(fn($time) => $time->toDateTimeString(), $times));
                if (count($uniqueTimes) === 1 && isset($times['modem'])) {
                    $allSensorDownCount++;
                    continue;
                }

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
                if ($earliestKey === 'modem') {
                    $allSensorDownCount++;
                } elseif ($earliestKey === 'router') {
                    $routerDownCount++;
                } elseif ($earliestKey === 'ap1' && isset($times['ap2']) && $times['ap1']->equalTo($times['ap2'])) {
                    $ap1And2DownCount++;
                } elseif ($earliestKey === 'ap1') {
                    $ap1DownCount++;
                } elseif ($earliestKey === 'ap2') {
                    $ap2DownCount++;
                }
            } else {
                $onlineCount++;
            }
        }

        return [
            'online' => $onlineCount,
            'all_sensor_down' => $allSensorDownCount,
            'router_down' => $routerDownCount,
            'ap1_down' => $ap1DownCount,
            'ap2_down' => $ap2DownCount,
            'ap1_and_2_down' => $ap1And2DownCount,
        ];
    }
}
