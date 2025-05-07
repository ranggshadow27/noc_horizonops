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

        $unWarningOpen = SweepingTicket::whereDate('created_at', $today)
            ->where('classification', 'UN WARNING')
            ->whereNot('status', 'CLOSED')
            ->count();

        $warningOpen = SweepingTicket::whereDate('created_at', $today)
            ->where('classification', 'WARNING')
            ->whereNot('status', 'CLOSED')
            ->count();

        $unWarningClose = SweepingTicket::whereDate('created_at', $today)
            ->where('classification', 'UN WARNING')
            ->where('status', 'CLOSED')
            ->count();

        $warningClose = SweepingTicket::whereDate('created_at', $today)
            ->where('classification', 'WARNING')
            ->where('status', 'CLOSED')
            ->count();

        $minorOpen = SweepingTicket::whereDate('created_at', $today)
            ->where('classification', 'MINOR')
            ->whereNot('status', 'CLOSED')
            ->count();

        $minorClose = SweepingTicket::whereDate('created_at', $today)
            ->where('classification', 'MINOR')
            ->where('status', 'CLOSED')
            ->count();


        return [
            Stat::make('Warning (6 Jam)', $warningClose)
                ->descriptionIcon('phosphor-check-circle-duotone')
                ->description("Closed today")
                ->color('success'),

            Stat::make('Warning Site (6 Jam)', $warningOpen)
                ->descriptionIcon('phosphor-check-circle-duotone')
                ->description("Opened today")
                ->color('warning'),

            Stat::make('Minor Site (12 Jam)', $minorClose)
                ->descriptionIcon('phosphor-check-circle-duotone')
                ->description("Closed today")
                ->color('success'),

            Stat::make('Minor Site (12 Jam)', $minorOpen)
                ->descriptionIcon('phosphor-exclamation-mark-duotone')
                ->description("Opened today")
                ->color('warning'),
        ];
    }
}
