<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\NmtTicketsTable;
use Filament\Pages\Page;

class TicketsMonitoringTable extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.tickets-monitoring-table';

    protected static ?string $navigationLabel = 'NMT Ticket Mon';
    protected static ?string $navigationGroup = 'Trouble Tickets';

    protected function getHeaderWidgets(): array
    {
        return [
            NmtTicketsTable::class,
            NmtTicketsTable::class,
        ];
    }
}
