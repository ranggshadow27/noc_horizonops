<?php

namespace App\Filament\Resources\TMODataResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\TMOData;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TMODataOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $user = Auth::user();
        $today = Carbon::today();

        // Ambil ID Role User
        $roleIds = $user->roles->pluck('id');

        // Query dasar
        $query = TMOData::whereDate('updated_at', $today);
        $queryTotal = TMOData::whereDate('created_at', '<=', $today);
        $totalTodayQuery = TMOData::whereDate('created_at', $today);

        // Jika user memiliki role id > 4, filter berdasarkan engineer_name
        if ($roleIds->some(fn($id) => $id > 4)) {
            $query->where('engineer_name', $user->name);
            $queryTotal->where('engineer_name', $user->name);
            $totalTodayQuery->where('engineer_name', $user->name);
        }

        // Jika user memiliki role id = 4, filter berdasarkan created_by
        if ($roleIds->contains(4)) {
            $query->where('created_by', $user->id);
            $queryTotal->where('created_by', $user->id);
            $totalTodayQuery->where('created_by', $user->id);
        }

        // Hitung jumlah berdasarkan status
        // $todayPending = (clone $query)->where('approval', 'Pending')->count();
        $totalPending = (clone $queryTotal)->where('approval', 'Pending')->count();

        $todayApproved = (clone $query)->where('approval', 'Approved')->count();
        $totalApproved = (clone $queryTotal)->where('approval', 'Approved')->count();

        $todayReject = (clone $query)->where('approval', 'Reject')->count();
        $totalReject = (clone $queryTotal)->where('approval', 'Reject')->count();

        // Hitung total hari ini dengan filter sesuai role user
        $totalToday = $totalTodayQuery->count();

        return [
            Stat::make('Total Pending TMO', $totalPending)
                ->descriptionIcon('phosphor-hourglass-high-duotone')
                ->description("TMO waiting for approval")
                ->color('warning'),

            Stat::make('Total Approved TMO', $totalApproved)
                ->descriptionIcon('phosphor-check-circle', 'before')
                ->description("{$todayApproved} TMO has been approved today")
                ->color('success'),

            Stat::make('Total Rejected TMO', $totalReject)
                ->descriptionIcon('phosphor-x', 'before')
                ->description("{$todayReject} TMO is rejected today")
                ->color('danger'),

            Stat::make('Today TMO', $totalToday)
                ->description('Overall TMO assigned today')
                ->color('primary'),
        ];
    }
}
