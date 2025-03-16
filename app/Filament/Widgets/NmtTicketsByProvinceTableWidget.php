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
            ->description("Live Highest Trouble Tickets by Province")
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
                    ->label('Province Name'),

                TextColumn::make('total_tickets')
                    ->label('Total Open')
                    ->badge()
                    ->color(function($state) {
                        if ($state >= 20) {
                            return 'danger';
                        } elseif ($state >= 8 && $state < 20) {
                            return 'warning';
                        }
                    })
                    ->formatStateUsing(fn($state) => $state . " Tickets"),

                TextColumn::make('area.area')
                    ->label('Area'),
            ]);
    }
}
