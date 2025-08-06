<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class DashboardHandover extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.dashboard-handover';

    protected static ?string $navigationLabel = 'Dashboard Handover';
    protected static ?string $navigationGroup = 'Trouble Tickets';

    protected ?string $heading = "";

    public function getColumns(): int | string | array
    {
        return 2;
    }
}
