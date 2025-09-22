<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Pages\SimplePage;

class PublicDashboard extends SimplePage
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.public-dashboard';

    public function getColumns(): int | string | array
    {
        return 2;
    }
}
