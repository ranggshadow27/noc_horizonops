<?php

namespace App\Livewire;

use App\Models\SiteLog;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class SiteMonitorTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public ?string $site_id = null;

    protected function getTableQuery(): Builder
    {
        $data = json_decode($this->site_id);
        Log::debug('SiteMonitorTable received site_id: ' . ($data->site_id ?? 'null'));
        return SiteLog::query()->where('site_id', $data->site_id);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('site_log_id')
                    ->label('Log ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format("d M Y"))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\IconColumn::make('modem_uptime')
                    ->label('Modem')
                    // ->formatStateUsing(fn($state) => $state * 10 . " Minutes")
                    ->icon(fn($state): string => $state >= 5 ? 'phosphor-arrow-circle-up-duotone' : ($state >= 3 && $state < 5 ? 'phosphor-arrow-circle-up-duotone' : 'phosphor-arrow-circle-down-duotone'))
                    ->color(fn($state): string => $state >= 5 ? 'success' : ($state >= 3 && $state < 5 ? 'warning' : 'danger'))
                    ->tooltip(fn($record): string => "Uptime : " . ($record->modem_uptime * 10) . ' minutes')
                    ->sortable(),

                Tables\Columns\IconColumn::make('traffic_uptime')
                    ->label('Traffic')
                    // ->formatStateUsing(fn($state) => $state * 10 . " Minutes")
                    ->icon(fn($state): string => $state >= 5 ? 'phosphor-arrow-circle-up-duotone' : ($state  >= 3 && $state < 5 ? 'phosphor-arrow-circle-up-duotone' : 'phosphor-arrow-circle-down-duotone'))
                    ->color(fn($state): string => $state >= 5 ? 'success' : ($state  >= 3 && $state  < 5 ? 'warning' : 'danger'))
                    ->tooltip(fn($record): string => "Uptime : " . ($record->traffic_uptime * 10) . ' minutes')
                    ->sortable(),

                Tables\Columns\TextColumn::make('nmt_ticket')
                    ->label('NMT Ticket')
                    ->tooltip(fn($record): string => $record->nmt_ticket !== "-" ? "Aging : " . ($record->nmtTicket->aging) . ' day(s)' : "")
                    ->badge()
                    ->color(fn($state) => $state === "-" ? "gray" : "danger")
                    ->sortable(),

                Tables\Columns\TextColumn::make('sensor_status')
                    ->label('Sensor Status')
                    ->sortable(),

                Tables\Columns\TextColumn::make('modem_last_up')
                    ->label('Last Up')
                    ->formatStateUsing(fn(SiteLog $record): string => $record->modem_last_up === null ? "Normal" : Carbon::parse($record->modem_last_up)->since())
                    // ->dateTime()
                    ->sortable(),

            ]);
    }

    public function render()
    {
        return view('livewire.site-monitor-table');
    }
}
