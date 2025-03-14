<?php

namespace App\Filament\Widgets;

use App\Models\NmtTickets;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class NmtTicketsByProvinceTableWidget extends BaseWidget
{

    public function table(Table $table): Table
    {
        return $table
            ->heading("Ticket Open per Province")
            ->paginated([5])
            ->query(
                NmtTickets::query()
                    ->selectRaw('MIN(ticket_id) as ticket_id, site_province, COUNT(*) as total_tickets') // Tambahkan ID untuk Filament
                    ->where('status', 'Open')
                    ->groupBy('site_province')
                    ->orderByDesc('total_tickets')
            )
            ->columns([
                TextColumn::make('site_province')
                    ->label('Provinsi'),

                TextColumn::make('total_tickets')
                    ->label('Total Tiket Open')
                    ->badge()
                    ->numeric(),

                TextColumn::make('area.area')
                    ->label('Area'),
            ]);
    }
}
