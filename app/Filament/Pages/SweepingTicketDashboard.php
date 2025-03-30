<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class SweepingTicketDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.sweeping-ticket-dashboard';

    protected static ?string $navigationLabel = 'Sweeping Ticket Dashboard';
    protected static ?string $navigationGroup = 'Trouble Tickets';

    protected ?string $heading = "";
}
