<?php

namespace App\Filament\Widgets;

use App\Models\SweepingTicket;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SweepingTTOverview extends BaseWidget
{
    protected function getStats(): array
    {

        $today = Carbon::today();

        $majorOpen = SweepingTicket::whereDate('created_at', $today)
            ->where('classification', 'MAJOR')
            ->whereNot('status', 'CLOSED')

            ->count();

        $minorOpen = SweepingTicket::whereDate('created_at', $today)
            ->where('classification', 'MINOR')
            ->whereNot('status', 'CLOSED')
            ->count();


        $majorClose = SweepingTicket::whereDate('created_at', $today)
            ->where('classification', 'MAJOR')
            ->where('status', 'CLOSED')
            ->count();

        $minorClose = SweepingTicket::whereDate('created_at', $today)
            ->where('classification', 'MINOR')
            ->where('status', 'CLOSED')
            ->count();


        return [
            Stat::make('Major Site (4 Hari)', $majorOpen)
                ->descriptionIcon('phosphor-exclamation-mark-duotone')
                ->description("Opened today")
                ->color('warning'),

            Stat::make('Major Site (4 Hari)', $majorClose)
                ->descriptionIcon('phosphor-check-circle-duotone')
                ->description("Closed today")
                ->color('success'),

            Stat::make('Minor Site (3 Hari)', $minorOpen)
                ->descriptionIcon('phosphor-exclamation-mark-duotone')
                ->description("Opened today")
                ->color('warning'),

            Stat::make('Minor Site (3 Hari)', $minorClose)
                ->descriptionIcon('phosphor-check-circle-duotone')
                ->description("Closed today")
                ->color('success'),

        ];
    }
}
