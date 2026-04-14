<?php

namespace App\Filament\Resources\SiteMonitorResource\Widgets;

use App\Models\SiteDetail;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GatewayOnlineStat extends BaseWidget
{
    protected static ?string $pollingInterval = '10m';

    protected function getStats(): array
    {
        // Ambil semua gateway yang unik dari SiteDetails
        $gateways = SiteDetail::select('gateway')
            ->distinct()
            ->whereNotIn('gateway', [
                'PMT Not Completed',
                'RELOKASI',
                '-'
            ])
            ->orderBy('gateway', 'asc')
            ->pluck('gateway');

        $stats = [];

        foreach ($gateways as $gateway) {
            if (empty($gateway)) continue;

            // Total site di gateway ini
            $totalSites = SiteDetail::where('gateway', $gateway)
                ->count();

            // Site yang modemnya "Up" (join ke SiteMonitor via site_id)
            $onlineSites = SiteDetail::where('gateway', $gateway)
                ->whereHas('siteMonitor', function ($query) {  // 'site' adalah nama relation di SiteDetails
                    $query->where('modem', 'Up');     // sesuaikan kolom statusnya
                })
                ->count();

            $percentage = $totalSites > 0
                ? round(($onlineSites / $totalSites) * 100, 2)
                : 0;

            $stats[] = Stat::make("Gateway {$gateway}", "{$onlineSites} Site Online")
                ->description("{$percentage}% of modems are currently online ")
                ->descriptionIcon($percentage >= 70 ? 'heroicon-o-check-circle' : 'heroicon-o-exclamation-circle')
                ->color($percentage >= 70 ? 'success' : ($percentage >= 50 ? 'warning' : 'danger'));
        }

        return $stats;
    }
}
