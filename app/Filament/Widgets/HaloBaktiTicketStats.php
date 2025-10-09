<?php

namespace App\Filament\Widgets;

use App\Models\HaloBaktiTicket;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class HaloBaktiTicketStats extends BaseWidget
{
    protected static ?int $sort = -2; // Tampil di atas table

    protected function getStats(): array
    {
        $today = Carbon::today();

        return [
            Stat::make('Closed Today', HaloBaktiTicket::where('status', 'Closed')
                ->whereDate('updated_at', $today)
                ->count())
                ->description('Tickets closed today')
                ->descriptionIcon('phosphor-check-circle-duotone')
                ->color('success'),
            Stat::make('On Progress', HaloBaktiTicket::where('status', 'On Progress')
                ->count())
                ->description('Tickets currently in progress')
                ->descriptionIcon('phosphor-spinner-gap-duotone')
                ->color('warning'),
            Stat::make('Unresolved', HaloBaktiTicket::where('status', 'Unresolved')
                ->count())
                ->description('Total unresolved tickets')
                ->descriptionIcon('phosphor-exclamation-mark-duotone')
                ->color('danger'),
            Stat::make('Total Tickets', HaloBaktiTicket::count())
                ->description('Overall Halo Bakti tickets')
                ->descriptionIcon('phosphor-bookmarks-duotone')
                ->color('gray'),
        ];
    }
}
