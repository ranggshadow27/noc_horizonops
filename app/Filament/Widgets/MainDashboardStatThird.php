<?php

namespace App\Filament\Widgets;

use App\Models\SweepingTicket;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MainDashboardStatThird extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';
    protected static bool $deferLoading = true;

    protected function getStats(): array
    {
        $today = Carbon::today();

        $warningOpen = SweepingTicket::whereDate('created_at', $today)
            ->where('classification', 'WARNING')
            ->whereNot('status', 'CLOSED')
            ->count();

        $warningClose = SweepingTicket::whereDate('created_at', $today)
            ->where('classification', 'WARNING')
            ->where('status', 'CLOSED')
            ->count();

        return [
            Stat::make('Warning Site (6 Jam)', $warningClose)
                ->descriptionIcon('phosphor-check-circle-duotone')
                ->description("Closed today")
                ->color('success'),

            Stat::make('Warning Site (6 Jam)', $warningOpen)
                ->descriptionIcon('phosphor-check-circle-duotone')
                ->description("Opened today")
                ->color('warning'),

            Stat::make('Overall Warning', $warningClose + $warningOpen)
                ->descriptionIcon('phosphor-check-circle-duotone')
                ->description("Warning today")
                ->color('gray'),


        ];
    }
}
