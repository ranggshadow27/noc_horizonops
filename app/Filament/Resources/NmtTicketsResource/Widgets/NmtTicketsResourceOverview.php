<?php

namespace App\Filament\Resources\NmtTicketsResource\Widgets;

use App\Models\NmtTickets;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class NmtTicketsResourceOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();

        $ticketsUp = NmtTickets::where('status', "OPEN")
            ->whereHas('siteMonitor', function ($query) {
                $query->where('modem_last_up', '=', null)
                    ->orWhere('modem_last_up', '>=', now()->subDay());
            })
            ->count();

        $todayTargetOnline = NmtTickets::whereDate('target_online', $today)->count();

        $todayOpen = NmtTickets::where('status', '=', 'OPEN')->count();

        $todayClosed = NmtTickets::where('status', '=', 'CLOSED')->whereDate('closed_date', $today)->count();

        return [
            Stat::make('Ticket Online', $ticketsUp)
                ->descriptionIcon('phosphor-check-circle-duotone')
                ->description("Ticket Currently Online")
                ->color('success'),

            Stat::make('Today Target Online', $todayTargetOnline)
                ->descriptionIcon('phosphor-hourglass-high-duotone')
                ->description("Ticket with Today Target Online")
                ->color('warning'),

            Stat::make('Trouble Ticket Open', $todayOpen)
                ->descriptionIcon('phosphor-exclamation-mark-duotone')
                ->description("Trouble Ticket Overall Open")
                ->color('warning'),

            Stat::make('Today Closed', $todayClosed)
                ->descriptionIcon('phosphor-check-circle-duotone')
                ->description("Total today Ticket Closed")
                ->color('success'),
        ];
    }
}
