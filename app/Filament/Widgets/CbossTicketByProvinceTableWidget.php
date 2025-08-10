<?php

namespace App\Filament\Widgets;

use App\Models\CbossTicket;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Widgets\TableWidget as BaseWidget;

class CbossTicketByProvinceTableWidget extends BaseWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->heading("Ticket Open per Trouble Category")
            ->description("Live Highest Trouble Categories")
            ->paginated([14])
            ->query(
                CbossTicket::query()
                    ->selectRaw('MIN(ticket_id) as ticket_id, trouble_category, COUNT(*) as total_tickets') // Tambahkan ID untuk Filament
                    ->whereNot('status', 'Closed')
                    ->groupBy('trouble_category')
                    ->orderByDesc('total_tickets')
            )
            ->columns([
                TextColumn::make('trouble_category')
                    // ->formatStateUsing(fn($state) => Str::title($state))
                    ->label('Category Name')
                    ->formatStateUsing(function ($state) {
                        return '• <span style="margin-left: 24px;"> ' . Str::title($state) . '</span>';
                    })
                    ->html(),

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

                // TextColumn::make('area')
                //     ->formatStateUsing(fn($record): string => $record->area->area)
                //     ->label('Area'),
            ]);
    }
}
