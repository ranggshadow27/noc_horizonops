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
            ->whereHas('siteMonitor', function ($query) {
                $query->where('status', '=', 'Normal');
            })
            ->count();

        $ticketsModemUp = NmtTickets::where('status', "OPEN")
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
            Stat::make('Ticket Up (All Sensor)', $ticketsUp)
                ->descriptionIcon('phosphor-check-circle-duotone')
                ->description("Ticket ready to be closed")
                ->color('success'),

            Stat::make('Ticket Modem Up', $ticketsModemUp)
                ->descriptionIcon('phosphor-check-circle-duotone')
                ->description("Ticket with only modem UP")
                ->color('success'),

            Stat::make('Today Target Online', $todayTargetOnline)
                ->descriptionIcon('phosphor-exclamation-mark-duotone')
                ->description("Ticket with today target online")
                ->color('gray'),

            Stat::make('Overall Ticket Progress', $todayOpen + $todayClosed)
                ->descriptionIcon('phosphor-hourglass-high-duotone')
                ->description($todayClosed . " Ticket Closed | " . $todayOpen .  " Ticket Open")
                ->color('warning'),
        ];
    }
}
