<?php

namespace App\Filament\Widgets;

use App\Models\NmtTickets;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class NmtTicketsTable extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';
    protected static bool $deferLoading = true;

    public function table(Table $table): Table
    {
        return $table
            ->heading("Ticket Open per Province")
            ->description("Live Highest Trouble Tickets by Province")
            // ->paginated([14])
            ->query(
                NmtTickets::query()
                    ->where('status', 'OPEN')
                    ->whereHas('siteMonitor', function ($query) {
                        $query->where('modem_last_up', '>=', now()->subDays(2))->orWhere('modem_last_up', '=', null);
                    })
                    ->with(['site', 'siteMonitor', 'area'])
            )
            ->columns([
                TextColumn::make('site_id')
                    ->label('Site ID')
                    ->sortable(),
                TextColumn::make('site.site_name')
                    ->label('Site Name')
                    ->sortable(),
                TextColumn::make('site_province')
                    ->label('Province')
                    ->sortable(),

                TextColumn::make('area.area')
                    ->label('Area'),
            ]);
    }
}
