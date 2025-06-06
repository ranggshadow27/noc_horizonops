<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\NmtTicketsTable;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;

class TicketsMonitoringTable extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.tickets-monitoring-table';

    protected static ?string $navigationLabel = 'NMT Ticket Mon';
    protected static ?string $navigationGroup = 'Filament Shield';

    protected function getHeaderWidgets(): array
    {
        return [
            NmtTicketsTable::class,
            NmtTicketsTable::class,
        ];
    }
}
