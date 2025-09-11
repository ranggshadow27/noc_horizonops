<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class SummarySite extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';
    protected static string $view = 'filament.pages.summary-site';
    protected static ?string $navigationLabel = 'Summary Site';
    protected static ?string $title = 'Summary Site';

    protected static ?string $navigationGroup = 'Site Management';

    protected int | string | array $columnSpan = 'full';

    public function getColumns(): int | string | array
    {
        return 1;
    }
}
