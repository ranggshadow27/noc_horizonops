<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class NMTTicketDashboard extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.n-m-t-ticket-dashboard';

    protected static ?string $navigationLabel = 'NMT Ticket Dashboard';
    protected static ?string $navigationGroup = 'Trouble Tickets';

    protected ?string $heading = "";

    // protected ?string $subheading = 'Custom Page Subheading';

    public function getColumns(): int | string | array
    {
        return 2;
    }

    public function getFooter(): ?View
    {
        return view('filament.pages.footer');
    }
}
