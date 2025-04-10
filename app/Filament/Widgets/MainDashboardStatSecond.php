<?php

namespace App\Filament\Widgets;

use App\Models\SweepingTicket;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MainDashboardStatSecond extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();

        $minorOpen = SweepingTicket::whereDate('created_at', $today)
            ->where('classification', 'MINOR')
            ->whereNot('status', 'CLOSED')
            ->count();

        $minorClose = SweepingTicket::whereDate('created_at', $today)
            ->where('classification', 'MINOR')
            ->where('status', 'CLOSED')
            ->count();

        $todayDown = SweepingTicket::whereDate('created_at', $today)
            ->whereNot('status', 'CLOSED')
            ->count();

        return [
            Stat::make('Minor Site (3 Hari)', $minorClose)
                ->descriptionIcon('phosphor-check-circle-duotone')
                ->description("Closed today")
                ->color('success'),

            Stat::make('Minor Site (3 Hari)', $minorOpen)
                ->descriptionIcon('phosphor-exclamation-mark-duotone')
                ->description("Opened today")
                ->color('warning'),

            Stat::make('Overall Down', $todayDown)
                ->descriptionIcon('phosphor-exclamation-mark-duotone')
                ->description("Site Down today")
                ->color('danger'),
        ];
    }
}
