<?php

namespace App\Filament\Widgets;

use App\Models\SweepingTicket;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SecondSweepingTTOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '300s';
    protected static bool $deferLoading = true;

    protected function getStats(): array
    {
        $today = Carbon::today();

        $majorOpen = SweepingTicket::whereDate('created_at', $today)
            ->where('classification', 'MAJOR')
            ->whereNot('status', 'CLOSED')
            ->count();

        $majorClose = SweepingTicket::whereDate('created_at', $today)
            ->where('classification', 'MAJOR')
            ->where('status', 'CLOSED')
            ->count();

        $todayDown = SweepingTicket::whereDate('created_at', $today)
            ->whereNot('status', 'CLOSED')
            ->count();

        $todayUp = SweepingTicket::whereDate('created_at', $today)
            ->where('status', 'CLOSED')
            ->count();


        return [
            Stat::make('Major Site (30 Jam)', $majorClose)
                ->descriptionIcon('phosphor-check-circle-duotone')
                ->description("Closed today")
                ->color('success'),

            Stat::make('Major Site (30 Jam)', $majorOpen)
                ->descriptionIcon('phosphor-exclamation-mark-duotone')
                ->description("Opened today")
                ->color('warning'),

            Stat::make('Overall Up', $todayUp)
                ->descriptionIcon('phosphor-check-circle-duotone')
                ->description("Site UP today")
                ->color('success'),

            Stat::make('Overall Down', $todayDown)
                ->descriptionIcon('phosphor-exclamation-mark-duotone')
                ->description("Site Down today")
                ->color('danger'),

        ];
    }
}
