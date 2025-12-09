<?php

namespace App\Filament\Widgets;

use App\Models\SweepingTicket;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MainDashboardStatSecond extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';
    protected static bool $deferLoading = true;

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

        // $todayDown = SweepingTicket::whereDate('created_at', $today)
        //     ->whereNot('status', 'CLOSED')
        //     ->count();

        return [
            Stat::make('Minor Site (12 Jam)', $minorClose)
                ->descriptionIcon('phosphor-check-circle-duotone')
                ->description("Closed today")
                ->color('success'),

            Stat::make('Minor Site (12 Jam)', $minorOpen)
                ->descriptionIcon('phosphor-exclamation-mark-duotone')
                ->description("Opened today")
                ->color('warning'),

            Stat::make('Overall Minor', $minorClose + $minorOpen)
                ->descriptionIcon('phosphor-exclamation-mark-duotone')
                ->description("Minor today")
                ->color('danger'),
        ];
    }
}
