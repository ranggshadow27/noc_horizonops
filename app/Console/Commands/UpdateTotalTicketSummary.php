<?php

namespace App\Console\Commands;

use App\Models\NmtTickets;
use App\Models\SiteMonitor;
use App\Models\TotalTicketSummary;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateTotalTicketSummary extends Command
{
    protected $signature = 'ticket:summary:update';
    protected $description = 'Update daily ticket summary based on sensor classifications';

    public function handle()
    {
        $today = Carbon::now()->format('Y-m-d');

        // Hitung counts seperti di widgetmu
        $ap1Down = NmtTickets::query()
            ->join('site_monitor', 'nmt_tickets.site_id', '=', 'site_monitor.site_id')
            ->where('site_monitor.sensor_status', 'AP1 Down')
            ->where('nmt_tickets.status', 'OPEN')
            ->count();

        $ap2Down = NmtTickets::query()
            ->join('site_monitor', 'nmt_tickets.site_id', '=', 'site_monitor.site_id')
            ->where('site_monitor.sensor_status', 'AP2 Down')
            ->where('nmt_tickets.status', 'OPEN')
            ->count();

        $ap1and2Down = NmtTickets::query()
            ->join('site_monitor', 'nmt_tickets.site_id', '=', 'site_monitor.site_id')
            ->where('site_monitor.sensor_status', 'AP1&2 Down')
            ->where('nmt_tickets.status', 'OPEN')
            ->count();

        $routerDown = NmtTickets::query()
            ->join('site_monitor', 'nmt_tickets.site_id', '=', 'site_monitor.site_id')
            ->where('site_monitor.sensor_status', 'Router Down')
            ->where('nmt_tickets.status', 'OPEN')
            ->count();

        $allSensorDown = NmtTickets::query()
            ->join('site_monitor', 'nmt_tickets.site_id', '=', 'site_monitor.site_id')
            ->where('site_monitor.sensor_status', 'All Sensor Down')
            ->where('nmt_tickets.status', 'OPEN')
            ->count();

        $totalOpenTicket = NmtTickets::query()
            ->where('status', 'OPEN')
            ->count();

        // Update or create record untuk hari ini
        TotalTicketSummary::updateOrCreate(
            ['summary_date' => $today],
            [
                'ap1_down' => $ap1Down,
                'ap2_down' => $ap2Down,
                'ap1_and_2_down' => $ap1and2Down,
                'router_down' => $routerDown,
                'all_sensor_down' => $allSensorDown,
                'total_ticket' => $totalOpenTicket,
            ]
        );

        $this->info('Ticket summary updated for ' . $today);
    }
}
