<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteMonitorResource\Pages;
use App\Filament\Resources\SiteMonitorResource\RelationManagers;
use App\Models\SiteDetail;
use App\Models\SiteMonitor;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class SiteMonitorResource extends Resource
{
    protected static ?string $model = SiteMonitor::class;

    protected static ?string $navigationIcon = 'phosphor-stack-duotone';

    protected static ?string $navigationGroup = 'Site Management';

    protected static ?string $navigationLabel = 'Monitoring Site';



    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('site_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('sitecode')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('modem')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('mikrotik')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('ap1')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('ap2')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('modem_last_up')
                    ->nullable(),
                Forms\Components\DateTimePicker::make('mikrotik_last_up')
                    ->nullable(),
                Forms\Components\DateTimePicker::make('ap1_last_up')
                    ->nullable(),
                Forms\Components\DateTimePicker::make('ap2_last_up')
                    ->nullable(),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function getTableQuery(): Builder
    {
        return parent::getTableQuery()->with(['site']);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('site_id')
                    ->sortable()
                    ->label('Site ID')
                    ->copyable()
                    ->searchable(['site_id', 'sitecode'])
                    ->description(fn(SiteMonitor $record): string => $record->sitecode),

                Tables\Columns\TextColumn::make('sitecode')
                    ->hidden(),

                Tables\Columns\TextColumn::make('site.province')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->description(fn(SiteMonitor $record): string => $record->site?->administrative_area ?? '-')
                    ->label('Province'),

                Tables\Columns\TextColumn::make('site.administrative_area')
                    ->hidden()
                    ->label('Administrative Area'),

                Tables\Columns\TextColumn::make('site.gateway')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->description(fn(SiteMonitor $record): string => $record->site?->spotbeam . ' | ' . $record->site?->ip_hub ?? '-')
                    ->label('Gateway'),

                Tables\Columns\TextColumn::make('status')->badge()->searchable()
                    ->sortable()->color(fn(string $state): string => match ($state) {
                        'Critical' => 'danger',
                        'Normal' => 'success',
                        'Minor' => 'primary',
                        'Major' => 'warning',
                    }),

                Tables\Columns\TextColumn::make('modem')->label('Modem')
                    ->sortable()->color(fn(string $state): string => match ($state) {
                        'Down' => 'danger',
                        'Up' => 'success',
                    })
                    ->weight(FontWeight::Bold)
                    ->description(fn(SiteMonitor $record): string => $record->modem_last_up === null ? "Normal" : $record->modem_last_up->since()),
                Tables\Columns\TextColumn::make('mikrotik')->label('Router')
                    ->sortable()->color(fn(string $state): string => match ($state) {
                        'Down' => 'danger',
                        'Up' => 'success',
                    })
                    ->weight(FontWeight::Bold)
                    ->description(fn(SiteMonitor $record): string => $record->mikrotik_last_up === null ? "Normal" : $record->mikrotik_last_up->since()),
                Tables\Columns\TextColumn::make('ap1')->label('AP 1')
                    ->sortable()->color(fn(string $state): string => match ($state) {
                        'Down' => 'danger',
                        'Up' => 'success',
                    })
                    ->weight(FontWeight::Bold)
                    ->description(fn(SiteMonitor $record): string => $record->ap1_last_up === null ? "Normal" : $record->ap1_last_up->since()),
                Tables\Columns\TextColumn::make('ap2')->label('AP 2')
                    ->sortable()->color(fn(string $state): string => match ($state) {
                        'Down' => 'danger',
                        'Up' => 'success',
                    })
                    ->weight(FontWeight::Bold)
                    ->description(fn(SiteMonitor $record): string => $record->ap2_last_up === null ? "Normal" : $record->ap2_last_up->since()),

                Tables\Columns\TextColumn::make('modem_last_up')
                    ->hidden()->since(),
                Tables\Columns\TextColumn::make('mikrotik_last_up')
                    ->hidden()->since(),
                Tables\Columns\TextColumn::make('ap1_last_up')
                    ->hidden()->since(),
                Tables\Columns\TextColumn::make('ap2_last_up')
                    ->hidden()->since(),
            ])
            ->filters([
                SelectFilter::make('modem')
                    ->options([
                        'up' => 'Up',
                        'down' => 'Down',
                    ])
                    ->label("Modem Status")
                    ->searchable(),
                SelectFilter::make('mikrotik')
                    ->options([
                        'up' => 'Up',
                        'down' => 'Down',
                    ])
                    ->label("Router Status")
                    ->searchable(),
                SelectFilter::make('ap1')
                    ->options([
                        'up' => 'Up',
                        'down' => 'Down',
                    ])
                    ->label("Access Point 1 Status")
                    ->searchable(),
                SelectFilter::make('ap2')
                    ->options([
                        'up' => 'Up',
                        'down' => 'Down',
                    ])
                    ->label("Access Point 2 Status")
                    ->searchable(),
                Filter::make('modem_last_up')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label("Modem Last UP"),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('modem_last_up', '>=', $date),
                            );
                    })->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['created_from'] ?? null) {
                            $indicators['created_from'] = 'Modem Last Up : ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                        }

                        return $indicators;
                    })
            ], layout: FiltersLayout::Dropdown)
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
                // ExportBulkAction::make()
                //     ->label("Export to Excel")
                //     ->icon('phosphor-file-xls-duotone')
                //     ->color('primary'),
            ])
            ->actions([
                Tables\Actions\Action::make('Details')
                    ->icon('heroicon-c-arrow-up-left')
                    // This is the important part!
                    ->infolist([
                        // Inside, we can treat this as any info list and add all the fields we want!
                        Section::make('Site Information')
                            ->schema([
                                TextEntry::make('site_id'),
                                TextEntry::make('sitecode'),
                                TextEntry::make('site.gateway'),
                                TextEntry::make('site.province'),
                            ])
                            ->columns(),
                        Section::make('Device Status')
                            ->schema([
                                TextEntry::make('modem')->badge()->label("Modem")
                                    ->color(fn(string $state): string => match ($state) {
                                        'Down' => 'danger',
                                        'Up' => 'success',
                                    }),
                                TextEntry::make('mikrotik')->badge()->label("Router")
                                    ->color(fn(string $state): string => match ($state) {
                                        'Down' => 'danger',
                                        'Up' => 'success',
                                    }),
                                TextEntry::make('ap1')->badge()->label("Access Point 1")
                                    ->color(fn(string $state): string => match ($state) {
                                        'Down' => 'danger',
                                        'Up' => 'success',
                                    }),
                                TextEntry::make('ap2')->badge()->label("Access Point 2")
                                    ->color(fn(string $state): string => match ($state) {
                                        'Down' => 'danger',
                                        'Up' => 'success',
                                    }),

                                TextEntry::make('modem_last_up')->badge()->label("Modem Last Up")
                                    ->dateTimeTooltip()->since()->default(Carbon::now()),
                                TextEntry::make('mikrotik_last_up')->badge()->label("Router Last Up")
                                    ->dateTimeTooltip()->since()->default(Carbon::now()),
                                TextEntry::make('ap1_last_up')->badge()->label("Access Point 1 Last Up")
                                    ->dateTimeTooltip()->since()->default(Carbon::now()),
                                TextEntry::make('ap2_last_up')->badge()->label("Access Point 2 Last Up")
                                    ->dateTimeTooltip()->since()->default(Carbon::now()),
                            ])
                            ->columns(4),

                    ])
                    ->modalSubmitAction(false)
                    ->modalHeading('Site Details'),
            ])
            ->recordUrl(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSiteMonitors::route('/'),
            'create' => Pages\CreateSiteMonitor::route('/create'),
            'edit' => Pages\EditSiteMonitor::route('/{record}/edit'),
        ];
    }
}
