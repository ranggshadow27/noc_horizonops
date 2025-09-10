<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\SummarySiteTable;
use App\Models\SiteDetail;
use App\Models\SiteLog;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SummarySite extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-table-cells';
    protected static string $view = 'filament.pages.summary-site';
    protected static ?string $navigationLabel = 'Summary Site';
    protected static ?string $title = 'Summary Site';

    protected int | string | array $columnSpan = 'full';

    public function getColumns(): int | string | array
    {
        return 1;
    }
}
