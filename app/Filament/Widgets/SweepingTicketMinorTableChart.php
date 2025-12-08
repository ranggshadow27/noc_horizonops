<?php

namespace App\Filament\Widgets;

use App\Models\CustomTicketData;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class SweepingTicketMinorTableChart extends BaseWidget
{

    // protected static string $view = 'filament.widgets.custom-table-widget';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('classification')
                    // ->badge()
                    // ->color(fn(string $state): string => match ($state) {
                    //     'Major' => 'danger',
                    //     'Minor' => 'warning',
                    //     'Warning' => 'gray',
                    //     'Un Warning' => 'gray',
                    // })
                    ->formatStateUsing(function ($state) {
                        return '• <span style="margin-left: 24px;"> ' . $state . '</span>';
                    })
                    ->html()
                    ->label('Classification'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Open' => 'warning',
                        'PIC Tidak Respon' => 'warning',
                        'Ter Follow Up' => 'info',
                        'Re Follow Up NSO' => 'info',
                        'Closed' => 'success',
                    })
                    ->label('Category'),

                Tables\Columns\TextColumn::make('jumlah')
                    ->label('Total')
                    ->numeric(),
            ])
            ->heading("Minor Sweeping")
            ->description("Today minor sweeping by category (live)")
            ->query(CustomTicketData::query())
            ->paginated(false)
            ->filters([])
            ->actions([])
            ->poll('300s')
            ->deferLoading()
            ->bulkActions([]);
    }
}
