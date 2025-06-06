<?php

namespace App\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;

class CbossTicketDashboard extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.cboss-ticket-dashboard';

    protected static ?string $navigationLabel = 'CBOSS Ticket Dashboard';
    protected static ?string $navigationGroup = 'Trouble Tickets';

    protected ?string $heading = "";

    public function getFooter(): ?View
    {
        return view('filament.pages.footer');
    }
}


