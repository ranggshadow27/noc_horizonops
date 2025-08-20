<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Update SiteMonitor setiap menit
        $schedule->call(function () {
            $apiDataService = new \App\Services\SiteMonitorService();
            $apiDataService->fetchAndSaveData();
        })->everyMinute();

        // Update SiteLog setiap 10 menit, 1 menit setelah SiteMonitor
        $schedule->command('site:sync-logs')->everyTenMinutes()->when(function () {
            return now()->minute % 10 === 1; // Jalan di menit ke-1 setiap 10 menit
        });

        // Hapus notifikasi lama setiap hari
        $schedule->call(function () {
            \Illuminate\Notifications\DatabaseNotification::where('created_at', '<', now()->subDays(3))->delete();
        })->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
