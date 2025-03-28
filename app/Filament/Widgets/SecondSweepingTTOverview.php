<?php

namespace App\Filament\Widgets;

use App\Models\SweepingTicket;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SecondSweepingTTOverview extends BaseWidget
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

        $todayDown = SweepingTicket::whereDate('created_at', $today)
            ->whereNot('status', 'CLOSED')
            ->count();

        $todayUp = SweepingTicket::whereDate('created_at', $today)
            ->where('status', 'CLOSED')
            ->count();


        return [
            Stat::make('Warning Site (2 Hari)', $warningOpen . " - " . $warningClose)
                ->descriptionIcon('phosphor-check-circle-duotone')
                ->description("Opened - Closed")
                ->color('gray'),

            Stat::make('Un Warning (12 Jam)', $unWarningOpen . " - " . $unWarningClose)
                ->descriptionIcon('phosphor-check-circle-duotone')
                ->description("Opened - Closed")
                ->color('gray'),

            Stat::make('Overall Open', $todayUp)
                ->descriptionIcon('phosphor-hourglass-high-duotone')
                ->description("Site UP today")
                ->color('success'),

            Stat::make('Overall Down', $todayDown)
                ->descriptionIcon('phosphor-cards-three-duotone')
                ->description("Site Down today")
                ->color('danger'),

        ];
    }
}
