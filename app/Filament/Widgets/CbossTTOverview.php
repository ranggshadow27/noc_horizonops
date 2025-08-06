<?php

namespace App\Filament\Widgets;

use App\Models\CbossTicket;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class CbossTTOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();

        $todayTotal = CbossTicket::whereDate('ticket_start', $today)
            ->count();

        $todayOpen = CbossTicket::whereNot('status', 'Closed')
            ->count();

        $todayClosed = CbossTicket::whereDate('ticket_end', $today)
            ->where('status', 'Closed')
            ->count();

        return [
            Stat::make('Ticket Open', $todayTotal)
                ->descriptionIcon('phosphor-exclamation-mark-duotone')
                ->description("Opened today")
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('danger'),

            Stat::make('Ticket Closed', $todayClosed)
                ->descriptionIcon('phosphor-check-circle-duotone')
                ->description("Closed today")
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),

            Stat::make('Total Ticket Open', $todayOpen)
                ->descriptionIcon('phosphor-hourglass-high-duotone')
                ->description("Tickets in Progress")
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('warning'),


        ];
    }
}
