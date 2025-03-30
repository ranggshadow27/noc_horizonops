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
            Stat::make('Un Warning (12 Jam)', $unWarningOpen . " - " . $unWarningClose)
                ->descriptionIcon('phosphor-check-circle-duotone')
                ->description("Opened - Closed")
                ->color('gray'),

            Stat::make('Warning Site (2 Hari)', $warningOpen . " - " . $warningClose)
                ->descriptionIcon('phosphor-check-circle-duotone')
                ->description("Opened - Closed")
                ->color('gray'),

            Stat::make('Minor Site (3 Hari)', $minorClose)
                ->descriptionIcon('phosphor-check-circle-duotone')
                ->description("Closed today")
                ->color('success'),

            Stat::make('Minor Site (3 Hari)', $minorOpen)
                ->descriptionIcon('phosphor-exclamation-mark-duotone')
                ->description("Opened today")
                ->color('warning'),
        ];
    }
}
