<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class SiteDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.site-dashboard';

    protected static ?string $navigationLabel = 'Site Dashboard';
    protected static ?string $navigationGroup = 'Site Management';

    protected ?string $heading = "";
}
