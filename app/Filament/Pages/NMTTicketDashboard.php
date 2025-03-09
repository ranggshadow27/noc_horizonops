<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class NMTTicketDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.n-m-t-ticket-dashboard';

    protected static ?string $navigationLabel = 'NMT Ticket Dashboard';
    protected ?string $heading = 'NMT Ticket Dashboard';
    protected static ?string $navigationGroup = 'Trouble Tickets';
}
