<?php

namespace App\Filament\Resources\SiteMonitorResource\Widgets;

use App\Models\SiteMonitor;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SiteMonitorOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '10m';

    protected function getStats(): array
    {
        $totalSites = SiteMonitor::count();
        $totalModemUp = SiteMonitor::where('modem', '=', 'Up')->count();
        $totalRouterUp = SiteMonitor::where('mikrotik', '=', 'Up')->count();
        $totalAp1Up = SiteMonitor::where('ap1', '=', 'Up')->count();
        $totalAp2Up = SiteMonitor::where('ap2', '=', 'Up')->count();

        $modemPercentage = $totalSites > 0 ? round(($totalModemUp / $totalSites) * 100, 2) : 0;
        $routerPercentage = $totalSites > 0 ? round(($totalRouterUp / $totalSites) * 100, 2) : 0;
        $ap1Percentage = $totalSites > 0 ? round(($totalAp1Up / $totalSites) * 100, 2) : 0;
        $ap2Percentage = $totalSites > 0 ? round(($totalAp2Up / $totalSites) * 100, 2) : 0;

        return [
            Stat::make('Total Modem UP', $totalModemUp . " Site")
                ->description("$modemPercentage% of modems are currently online")->color('primary')
                ->descriptionIcon('phosphor-check-circle-duotone'),
            Stat::make('Total Router UP', $totalRouterUp . " Site")
                ->description("$routerPercentage% of router sensors are online")->color('primary')
                ->descriptionIcon('phosphor-check-circle-duotone'),
            Stat::make('Total Access Point 1 UP', $totalAp1Up . " Site")
                ->description("$ap1Percentage% of access point 1 sensors are online")->color('primary')
                ->descriptionIcon('phosphor-check-circle-duotone'),
            Stat::make('Total Access Point 2 UP', $totalAp2Up . " Site")
                ->description("$ap2Percentage% of access point 2 sensors are online")->color('primary')
                ->descriptionIcon('phosphor-check-circle-duotone'),
        ];
    }
}
