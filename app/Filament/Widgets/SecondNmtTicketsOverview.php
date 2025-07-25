<?php

namespace App\Filament\Widgets;

use App\Models\NmtTickets;
use App\Models\SweepingTicket;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SecondNmtTicketsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();

        // $todayTotal = NmtTickets::whereDate('date_start', $today)
        //     ->count();

        // $todayOpen = NmtTickets::where('status', 'OPEN')
        //     ->count();

        // $todayClosed = NmtTickets::whereDate('closed_date', $today)
        //     ->where('status', 'CLOSED')
        //     ->count();

        $ttAgingAvg = intval(NmtTickets::query()
            ->join('site_monitor', 'nmt_tickets.site_id', '=', 'site_monitor.site_id')
            ->where('nmt_tickets.status', 'OPEN')
            ->where('site_monitor.sensor_status', 'All Sensor Down')
            ->average('nmt_tickets.aging') ?? 0);

        $closedbyNSO = NmtTickets::where('problem_detail', 'KUNJUNGAN')
            ->whereDate('closed_date', $today)
            ->where('status', 'CLOSED')
            ->count();

        $closedbyNOC = NmtTickets::whereNot('problem_detail', 'KUNJUNGAN')
            ->whereDate('closed_date', $today)
            ->where('status', 'CLOSED')
            ->count();

        $today = Carbon::today();

        $majorOpen = SweepingTicket::whereDate('created_at', $today)
            ->where('classification', 'MAJOR')
            ->whereNot('status', 'CLOSED')
            ->count();

        return [
            Stat::make('Today NOC Progress', $closedbyNOC)
                ->descriptionIcon('phosphor-handshake-duotone')
                ->description("Ticket Closed")
                ->color('success'),

            Stat::make('Today O&M Progress', $closedbyNSO)
                ->descriptionIcon('phosphor-hand-deposit-duotone')
                ->description("Ticket Resolved")
                ->color('success'),

            Stat::make('Ticket Aging', $ttAgingAvg . " day(s)")
                ->descriptionIcon('phosphor-clock-countdown-duotone')
                ->description("Average days open")
                ->color($ttAgingAvg > 14 ? 'danger' : ($ttAgingAvg > 7 ? 'warning' : 'success')),

            Stat::make('Open Tomorrow', $majorOpen)
                ->descriptionIcon('phosphor-push-pin-duotone')
                ->description("Predicted Tickets")
                ->color('warning'),
        ];
    }
}
