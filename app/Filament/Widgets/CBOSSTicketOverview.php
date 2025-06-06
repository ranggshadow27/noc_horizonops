<?php

namespace App\Filament\Widgets;

use App\Models\CbossTicket;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CBOSSTicketOverview extends BaseWidget
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
                ->color('danger'),

            Stat::make('Ticket Closed', $todayClosed)
                ->descriptionIcon('phosphor-check-circle-duotone')
                ->description("Closed today")
                ->color('success'),

            Stat::make('Total Ticket Open', $todayOpen)
                ->descriptionIcon('phosphor-hourglass-high-duotone')
                ->description("Tickets in Progress")
                ->color('warning'),

            Stat::make('Overall Tickets', $todayOpen + $todayClosed)
                ->descriptionIcon('phosphor-cards-three-duotone')
                ->description("Ticket assigned")
                ->color('gray'),

            // Stat::make('Problem Type', $teknis . ' / ' . $nonTeknis)
            //     ->descriptionIcon('phosphor-check-circle')
            //     ->description("TT Open by Technical - NonTechnical")
            //     ->color('gray'),

        ];
    }
}
