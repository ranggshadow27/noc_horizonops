<?php

namespace App\Filament\Widgets;

use App\Models\NmtTickets;
use App\Models\SiteMonitor;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Log;

class NmtTicketSensorClassification extends BaseWidget
{
    protected static string $view = 'filament.widgets.sensor-status-widget';

    // protected int | string | array $columnSpan = 6;

    protected static ?string $pollingInterval = '60s';


    public function getData(): array
    {
        $ap1Down = NmtTickets::query()
            ->join('site_monitor', 'nmt_tickets.site_id', '=', 'site_monitor.site_id')
            ->where('site_monitor.sensor_status', 'AP1 Down')
            ->where('nmt_tickets.status', 'OPEN')
            ->count();

        $ap2Down = NmtTickets::query()
            ->join('site_monitor', 'nmt_tickets.site_id', '=', 'site_monitor.site_id')
            ->where('site_monitor.sensor_status', 'AP2 Down')
            ->where('nmt_tickets.status', 'OPEN')
            ->count();

        $ap1and2Down = NmtTickets::query()
            ->join('site_monitor', 'nmt_tickets.site_id', '=', 'site_monitor.site_id')
            ->where('site_monitor.sensor_status', 'AP1&2 Down')
            ->where('nmt_tickets.status', 'OPEN')
            ->count();

        $routerDown = NmtTickets::query()
            ->join('site_monitor', 'nmt_tickets.site_id', '=', 'site_monitor.site_id')
            ->where('site_monitor.sensor_status', 'Router Down')
            ->where('nmt_tickets.status', 'OPEN')
            ->count();

        $allSensor = NmtTickets::query()
            ->join('site_monitor', 'nmt_tickets.site_id', '=', 'site_monitor.site_id')
            ->where('site_monitor.sensor_status', 'All Sensor Down')
            ->where('nmt_tickets.status', 'OPEN')
            ->count();

        $online = NmtTickets::query()
            ->join('site_monitor', 'nmt_tickets.site_id', '=', 'site_monitor.site_id')
            ->where('site_monitor.sensor_status', 'Online')
            ->where('nmt_tickets.status', 'OPEN')
            ->count();

        // $ttAgingAvgAllSensorDown = intval(NmtTickets::query()
        //     ->join('site_monitor', 'nmt_tickets.site_id', '=', 'site_monitor.site_id')
        //     ->where('nmt_tickets.status', 'OPEN')
        //     ->where('site_monitor.sensor_status', 'All Sensor Down')
        //     ->average('nmt_tickets.aging') ?? 0);

        $ttAgingAvgNonAllSensorDown = intval(NmtTickets::query()
            ->join('site_monitor', 'nmt_tickets.site_id', '=', 'site_monitor.site_id')
            ->where('nmt_tickets.status', 'OPEN')
            ->whereNot('site_monitor.sensor_status', 'All Sensor Down')
            ->average('nmt_tickets.aging') ?? 0);

        return [
            'online' => $routerDown + $ap1Down + $ap2Down + $ap1and2Down,
            'all_sensor_down' => $allSensor,
            'router_down' => $routerDown,
            'ap1_down' => $ap1Down,
            'ap2_down' => $ap2Down,
            'ap1_and_2_down' => $ap1and2Down,
            'aging' => $ttAgingAvgNonAllSensorDown,
        ];
    }
}
