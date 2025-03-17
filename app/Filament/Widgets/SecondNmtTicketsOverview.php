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

        $todayTotal = NmtTickets::whereDate('date_start', $today)
            ->count();

        $todayOpen = NmtTickets::where('status', 'OPEN')
            ->count();

        $todayClosed = NmtTickets::whereDate('closed_date', $today)
            ->where('status', 'CLOSED')
            ->count();

        $nonTeknis = NmtTickets::where('problem_type', 'NON TEKNIS')
            ->where('status', 'OPEN')
            ->count();

        $teknis = NmtTickets::where('problem_type', 'TEKNIS')
            ->where('status', 'OPEN')
            ->count();

        return [
            Stat::make('Problem Type', $teknis . ' - ' . $nonTeknis)
                ->descriptionIcon('phosphor-wrench-duotone')
                ->description("Technical - NonTechnical TT")
                ->color('gray'),

                Stat::make('Closed Ticket', $teknis . ' - ' . $nonTeknis)
                ->descriptionIcon('phosphor-handshake-duotone')
                ->description("Closed by NSO - NOC")
                ->color('success'),


            Stat::make('Another Sample', $todayClosed)
                ->descriptionIcon('phosphor-check-circle')
                ->description("Loremipsumdolorsit today")
                ->color('success'),

        ];
    }
}
