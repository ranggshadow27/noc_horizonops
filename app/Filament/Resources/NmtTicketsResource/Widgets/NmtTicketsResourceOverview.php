<?php

namespace App\Filament\Resources\NmtTicketsResource\Widgets;

use App\Models\NmtTickets;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class NmtTicketsResourceOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '20s';

    protected function getStats(): array
    {
        $today = Carbon::today();

        $ticketsUp = NmtTickets::where('status', "OPEN")
            ->whereHas('siteMonitor', function ($query) {
                $query
                    ->where('modem_last_up', '=', null)
                    ->orWhere('modem_last_up', '>=', now()->subDay());
            })
            ->count();

        $todayTargetOnline = NmtTickets::whereDate('target_online', $today)
            ->where('status', '=', 'OPEN')
            ->count();

        $todayOpen = NmtTickets::where('status', '=', 'OPEN')
            ->count();

        $todayClosed = NmtTickets::where('status', '=', 'CLOSED')
            ->whereDate('closed_date', $today)
            ->count();

        return [
            Stat::make('Ticket Up (Modem Online)', $ticketsUp)
                ->descriptionIcon('phosphor-check-circle-duotone')
                ->description("Ticket ready to be closed")
                ->color('success'),

            Stat::make('Today Target Online', $todayTargetOnline)
                ->descriptionIcon('phosphor-hourglass-high-duotone')
                ->description("Ticket with Today Target Online")
                ->color('gray'),

            Stat::make('Open Ticket', $todayOpen)
                ->descriptionIcon('phosphor-exclamation-mark-duotone')
                ->description("Overall Ticket on Progress")
                ->color('warning'),

            Stat::make('Today Closed', $todayClosed)
                ->descriptionIcon('phosphor-check-circle-duotone')
                ->description("Today Ticket Closed")
                ->color('success'),
        ];
    }
}
