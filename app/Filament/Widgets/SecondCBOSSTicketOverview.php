<?php

namespace App\Filament\Widgets;

use App\Models\CbossTicket;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class SecondCBOSSTicketOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();

        $averageAging = CbossTicket::query()
            ->where('status', '!=', 'Closed')
            ->select(DB::raw('AVG(DATEDIFF(CURDATE(), ticket_start)) as average_aging'))
            ->first()
            ->average_aging;

        // Format nilai rata-rata (jika null, set ke 0)
        $averageAging = $averageAging ? round($averageAging, 0) : 0;

        $iduProblem = CbossTicket::where('problem_map', "MASALAH PERANGKAT IDU")
            ->whereNot('status', 'Closed')
            ->count();

            $oduProblem = CbossTicket::where('problem_map', "MASALAH PERANGKAT ODU")
            ->whereNot('status', 'Closed')
            ->count();

        $closedbyTMO = CbossTicket::where('detail_action', "%LIKE%", "Telah dilakukan TMO dengan detail sebagai berikut:")
            ->whereDate('ticket_end', $today)
            ->where('status', 'Closed')
            ->count();

        $overdueCount = CbossTicket::query()
            ->whereNot('status', 'Closed')
            ->whereRaw('DATEDIFF(CURDATE(), ticket_start) > 20')
            ->count();

        return [
            Stat::make('Device Problem', $iduProblem + $oduProblem)
                ->descriptionIcon('phosphor-push-pin-duotone')
                ->description("Tickets need Visit")
                ->color('warning'),

            Stat::make("Today TMO", $closedbyTMO)
                ->descriptionIcon('phosphor-hand-deposit-duotone')
                ->description("Closed by TMO")
                ->color('success'),

            Stat::make('Ticket Aging', $averageAging . " days")
                ->descriptionIcon('phosphor-clock-countdown-duotone')
                ->description("Average days open")
                ->color($averageAging > 14 ? 'danger' : ($averageAging > 7 ? 'warning' : 'success')),

            Stat::make('Overdue Tickets', $overdueCount)
                ->descriptionIcon('phosphor-exclamation-mark-duotone')
                ->description("TT Open >20days")
                ->color($overdueCount > 14 ? 'danger' : ($overdueCount > 7 ? 'warning' : 'success')),
        ];
    }
}
