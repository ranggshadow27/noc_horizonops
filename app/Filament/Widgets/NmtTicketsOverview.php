<?php

namespace App\Filament\Widgets;

use App\Models\NmtTickets;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class NmtTicketsOverview extends BaseWidget
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
            Stat::make('Trouble Ticket Open', $todayTotal)
                ->descriptionIcon('phosphor-exclamation-mark-duotone')
                ->description("Opened today")
                ->color('danger'),

            Stat::make('Trouble Ticket Closed', $todayClosed)
                ->descriptionIcon('phosphor-check-circle-duotone')
                ->description("Done Close today")
                ->color('success'),

            Stat::make('Overall Ticket Open', $todayOpen)
                ->descriptionIcon('phosphor-hourglass-high-duotone')
                ->description("Tickets in Progress")
                ->color('warning'),

            Stat::make('Today Tickets', $todayOpen + $todayClosed)
                ->descriptionIcon('phosphor-cards-three-duotone')
                ->description("Currently Assigned")
                ->color('gray'),

            // Stat::make('Problem Type', $teknis . ' / ' . $nonTeknis)
            //     ->descriptionIcon('phosphor-check-circle')
            //     ->description("TT Open by Technical - NonTechnical")
            //     ->color('gray'),

        ];
    }
}
