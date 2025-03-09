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
        // $totalPending = (clone $queryTotal)->where('tmo_type', 'Pending')->count();

        $todayApproved = (clone $query)->where('tmo_type', 'Preventive Maintenance')->count();
        $totalApproved = (clone $queryTotal)->where('tmo_type', 'Preventive Maintenance')->count();

        $todayReject = (clone $query)->where('tmo_type', 'Corrective Maintenance')->count();
        $totalReject = (clone $queryTotal)->where('tmo_type', 'Corrective Maintenance')->count();

        // Hitung total hari ini dengan filter sesuai role user
        $totalToday = $totalTodayQuery->count();
        $totalTMO = $queryTotal->count();

        return [
            Stat::make('Today TMO', $totalTMO)
                ->descriptionIcon('phosphor-check-circle')
                ->description("Total TMO assigned today")
                ->color('warning'),

            Stat::make('Today PM TMO', $totalApproved)
                ->descriptionIcon('phosphor-check-circle')
                ->description("Preventive Maintenance TMO assigned today")
                ->color('primary'),

            Stat::make('Today CM TMO', $totalReject)
                ->descriptionIcon('phosphor-check-circle')
                ->description("Corrective Maintenance TMO is done today")
                ->color('primary'),

            Stat::make('Overall TMO', $totalToday)
                ->descriptionIcon('phosphor-check-circle')
                ->description('TMO has been assigned')
                ->color('success'),
        ];
    }
}
