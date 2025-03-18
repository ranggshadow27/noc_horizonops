<?php

namespace App\Filament\Widgets;

use App\Models\NmtTickets;
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

        $nonTeknis = NmtTickets::where('problem_type', 'NON TEKNIS')
            ->where('status', 'OPEN')
            ->count();

        $teknis = NmtTickets::where('problem_type', 'TEKNIS')
            ->where('status', 'OPEN')
            ->count();

        $ttAgingAvg = intval(NmtTickets::where('status', 'OPEN')->average('aging'));

        $closedbyNSO = NmtTickets::where('problem_detail', 'KUNJUNGAN')
            ->whereDate('closed_date', $today)
            ->where('status', 'CLOSED')
            ->count();

        $closedbyNOC = NmtTickets::whereNot('problem_detail', 'KUNJUNGAN')
            ->whereDate('closed_date', $today)
            ->where('status', 'CLOSED')
            ->count();

        return [


            Stat::make('Today NOC Progress', $closedbyNOC)
                ->descriptionIcon('phosphor-handshake-duotone')
                ->description("Ticket Closed")
                ->color('success'),

            Stat::make('Today NSO Progress', $closedbyNSO)
                ->descriptionIcon('phosphor-hand-deposit-duotone')
                ->description("Ticket Resolved")
                ->color('success'),

            Stat::make('Ticket Aging', $ttAgingAvg . " days")
                ->descriptionIcon('phosphor-clock-countdown-duotone')
                ->description("Average days open")
                ->color($ttAgingAvg > 14 ? 'danger' : ($ttAgingAvg > 7 ? 'warning' : 'success')),

            Stat::make('Ticket Problem Type', $teknis . ' - ' . $nonTeknis)
                ->descriptionIcon('phosphor-wrench-duotone')
                ->description("Teknis/NonTeknis")
                ->color('gray'),

        ];
    }
}
