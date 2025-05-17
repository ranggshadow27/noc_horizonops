<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\NmtTickets;
use Illuminate\Support\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Forms\Components\Section;
use Illuminate\Support\Facades\Log;

class TicketsMonitoring extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.tickets-monitoring';

    protected static ?string $navigationLabel = 'NMT Ticket Monitoring';
    protected static ?string $navigationGroup = 'Trouble Tickets';

    public function getSections()
    {
        // Query dari database: NmtTicket dengan status OPEN dan modem_last_up < 2 hari
        $tickets = NmtTickets::query()
            ->where('status', 'OPEN')
            ->whereHas('siteMonitor', function ($query) {
                $query->where('modem_last_up', '>=', now()->subDays(2))->orWhere('modem_last_up', '=', null);
            })
            ->with(['site', 'siteMonitor', 'area'])
            ->get();

        // Log jumlah tickets untuk debug
        Log::info('Jumlah tickets yang difilter dari DB: ' . $tickets->count());

        // Format data ke dalam array untuk blade
        $sections = $tickets->map(function ($ticket, $index) {
            $site = $ticket->site;
            $siteMonitor = $ticket->siteMonitor;
            $area = $ticket->area;

            // Log data ticket untuk debug
            if ($site && $siteMonitor && $area) {
                Log::info("Ticket {$index} heading: {$site->site_id} - {$site->site_name} - {$area->province}");
            }

            if ($ticket->aging > 1) {
                $aging = "{$ticket->aging} days";
            } else {
                $aging = "{$ticket->aging} day";
            }

            $formatTargetOnline = $ticket->target_online ? Carbon::parse($ticket->target_online)->format("d M Y") : "None";

            if ($site && $siteMonitor && $area) {
                return [
                    'site_id' => $ticket->site_id,
                    'site_name' => $site->site_name,
                    'province' => $area->province,
                    'area' => $area->area,
                    'modem_last_up' => $siteMonitor->modem_last_up ?? "Online",
                    'aging' => $aging,
                    'ticket_id' => $ticket->ticket_id,
                    'problem_classification' => $ticket->problem_classification,
                    'problem_type' => $ticket->problem_type,
                    'target_online' => $formatTargetOnline ?? "-",
                ];
            }
            return null;
        })->filter()->toArray();

        // Log jumlah sections untuk debug
        Log::info('Jumlah sections yang dihasilkan: ' . count($sections));

        return $sections;
    }
}
