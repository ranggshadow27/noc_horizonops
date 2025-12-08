<?php

namespace App\Filament\Widgets;

use App\Models\CbossTicket;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class SecondCbossTTOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';
    protected static bool $deferLoading = true;

    protected function getStats(): array
    {
        $today = Carbon::today();

        $todayTotal = CbossTicket::whereDate('ticket_start', $today)->count();

        $todayOpen = CbossTicket::whereNot('status', 'Closed')->count();

        $todayClosed = CbossTicket::whereDate('ticket_end', $today)->where('status', 'Closed')->count();

        // Tiket Teknis: problem_map mengandung "MASALAH PERANGKAT ODU" atau "MASALAH PERANGKAT IDU"
        $technicalTickets = CbossTicket::where(function ($query) {
            $query->where('problem_map', 'like', '%MASALAH PERANGKAT ODU%')->orWhere('problem_map', 'like', '%MASALAH PERANGKAT IDU%');
        })
            ->whereNot('status', 'Closed')
            ->count();

        // Tiket Non-Teknis: selain teknis
        $nonTechnicalTickets = CbossTicket::where(function ($query) {
            $query->where('problem_map', 'not like', '%MASALAH PERANGKAT ODU%')
                ->where('problem_map', 'not like', '%MASALAH PERANGKAT IDU%');
        })
            ->whereNot('status', 'Closed')
            ->count();

        return [
            Stat::make('Technical Tickets', $technicalTickets)
                ->descriptionIcon('phosphor-gear-duotone')
                ->description('ODU/IDU Issues')
                ->color('primary'),

            Stat::make('Non-Technical Tickets', $nonTechnicalTickets)
                ->descriptionIcon('phosphor-clipboard-text-duotone')
                ->description('Other Issues')
                ->color('info'),

            Stat::make('Overall Tickets', $todayOpen + $todayClosed)
                ->descriptionIcon('phosphor-cards-three-duotone')
                ->description("Ticket assigned")
                // ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('gray'),
        ];
    }
}
