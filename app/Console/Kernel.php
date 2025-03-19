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
        // $schedule->command('inspire')->hourly();

        $schedule->call(function () {
            $apiDataService = new \App\Services\SiteMonitorService();
            $apiDataService->fetchAndSaveData();
        })->everyMinute();  // Bisa disesuaikan dengan interval yang diinginkan

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
