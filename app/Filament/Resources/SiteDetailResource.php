<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteDetailResource\Pages\ListSiteDetails;
use App\Models\AreaList;
use App\Models\SiteDetail;
use App\Models\Device;
use App\Models\DeviceNetwork;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Enums\FiltersLayout;

class SiteDetailResource extends Resource
{

    protected static ?string $model = SiteDetail::class;

    protected static ?string $navigationIcon = 'phosphor-database-duotone';

    protected static ?string $navigationGroup = 'Site Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Form untuk SiteDetail
                Forms\Components\Fieldset::make('site_details')
                    ->label("")
                    ->schema([
                        Forms\Components\TextInput::make('site_id')
                            ->required()
                            ->label('Site ID'),
                        Forms\Components\TextInput::make('site_name')
                            ->required()
                            ->label('Site Name'),
                        Forms\Components\TextInput::make('province')
                            ->required()
                            ->label('Province'),
                        Forms\Components\TextInput::make('administrative_area')
                            ->required()
                            ->label('Administrative Area'),
                        Forms\Components\TextInput::make('address')
                            ->required()
                            ->label('Address'),
                        Forms\Components\TextInput::make('latitude')
                            ->required()
                            ->label('Latitude'),
                        Forms\Components\TextInput::make('longitude')
                            ->required()
                            ->label('Longitude'),
                        Forms\Components\TextInput::make('spotbeam')
                            ->required()
                            ->label('Spotbeam'),
                        Forms\Components\TextInput::make('ip_hub')
                            ->required()
                            ->label('IP Hub'),
                        Forms\Components\TextInput::make('gateway')
                            ->required()
                            ->label('Gateway'),
                        Forms\Components\TextInput::make('power_source')
                            ->required()
                            ->label('Power Source'),
                        Forms\Components\TextInput::make('batch')
                            ->required()
                            ->label('Batch'),
                        Forms\Components\TextInput::make('pic_name')
                            ->required()
                            ->label('PIC Name'),
                        Forms\Components\TextInput::make('pic_number')
                            ->required()
                            ->label('PIC Number'),
                        Forms\Components\TextInput::make('installer_name')
                            ->required()
                            ->label('Installer Name'),
                        Forms\Components\TextInput::make('installer_number')
                            ->required()
                            ->label('Installer Number'),
                    ]),

                // Data dari Device

                Forms\Components\Repeater::make('devices')
                    ->relationship('devices')
                    ->schema([
                        Forms\Components\TextInput::make('site_id')
                            ->label('Site ID'),
                        Forms\Components\TextInput::make('rack_sn')
                            ->label('Rack SN'),
                        Forms\Components\TextInput::make('antenna_sn')
                            ->label('Antenna SN'),
                        Forms\Components\TextInput::make('antenna_type')
                            ->label('Antenna Type'),
                        Forms\Components\TextInput::make('transceiver_sn')
                            ->label('Transceiver SN'),
                        Forms\Components\TextInput::make('transceiver_type')
                            ->label('Transceiver Type'),
                        Forms\Components\TextInput::make('modem_sn')
                            ->label('Modem SN'),
                        Forms\Components\TextInput::make('modem_type')
                            ->label('Modem Type'),
                        Forms\Components\TextInput::make('router_sn')
                            ->label('Router SN'),
                        Forms\Components\TextInput::make('router_type')
                            ->label('Router Type'),
                        Forms\Components\TextInput::make('ap1_sn')
                            ->label('Access Point 1 SN'),
                        Forms\Components\TextInput::make('ap1_type')
                            ->label('Access Point 1 Type'),
                        Forms\Components\TextInput::make('ap2_sn')
                            ->label('Access Point 2 SN'),
                        Forms\Components\TextInput::make('ap2_type')
                            ->label('Access Point 2 Type'),
                        Forms\Components\TextInput::make('stabilizer_sn')
                            ->label('Stabillizer SN'),
                        Forms\Components\TextInput::make('stabilizer_type')
                            ->label('Stabillizer Type'),
                    ])
                    ->columns(2)->columnSpanFull()
                    ->hiddenLabel()->itemLabel("Device Details")
                    ->collapsible()->collapsed(),



                // Data dari DeviceNetwork
                Forms\Components\Repeater::make('deviceNetworks')
                    ->relationship('deviceNetworks')
                    ->schema([
                        Forms\Components\TextInput::make('modem_ip')
                            ->label('Modem IP'),
                        Forms\Components\TextInput::make('router_ip')
                            ->label('Router IP'),
                        Forms\Components\TextInput::make('ap1_ip')
                            ->label('AP1 IP'),
                        Forms\Components\TextInput::make('ap2_ip')
                            ->label('AP2 IP'),
                    ])
                    ->columns(2)->columnSpanFull()
                    ->hiddenLabel()->itemLabel("Network Details")
                    ->collapsible()->collapsed(),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('site_id')
                    ->sortable()
                    ->label('Site ID')
                    ->copyable()
                    ->searchable(['site_id', 'site_name'])
                    ->description(fn(SiteDetail $record): string => $record->site_name),

                Tables\Columns\TextColumn::make('site_name')
                    ->label('Site Name')
                    ->hidden(),

                Tables\Columns\TextColumn::make('province')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->description(fn(SiteDetail $record): string => $record->area->area ?? '-')
                    ->label('Province'),

                Tables\Columns\TextColumn::make('administrative_area')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->description(fn(SiteDetail $record): string => 'Lat : ' . ($record->latitude ?? '-') . ' | Long : ' . ($record->longitude ?? '-'))
                    ->label('Administrative Area'),

                Tables\Columns\TextColumn::make('address')
                    ->label('Address')
                    ->hidden(),

                Tables\Columns\TextColumn::make('latitude')
                    ->label('Latitude')
                    ->hidden(),

                Tables\Columns\TextColumn::make('longitude')
                    ->label('Longitude')
                    ->hidden(),

                Tables\Columns\TextColumn::make('siteMonitor.status')
                    ->label('Status')->badge()->searchable()
                    ->sortable()->color(fn(string $state): string => match ($state) {
                        'Critical' => 'danger',
                        'Major' => 'warning',
                        'Minor' => 'apricot',
                        'Warning' => 'celadon',
                        'Normal' => 'success',
                    }),

                Tables\Columns\TextColumn::make('siteMonitor.sensor_status')
                    ->label('Sensor Status')
                    ->default("Online")
                    ->sortable()
                    ->searchable(),

                // Tables\Columns\TextColumn::make('deviceNetworks.modem_ip')
                //     ->label('IP Modem')
                //     // ->badge()
                //     ->copyable()
                //     ->searchable(),

                Tables\Columns\TextColumn::make('gateway')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->description(fn(SiteDetail $record): string => $record->spotbeam . ' | ' . $record->ip_hub ?? '-')
                    ->label('Gateway'),

                Tables\Columns\TextColumn::make('ip_hub')
                    ->label('ip_hub')
                    ->hidden(),

                Tables\Columns\TextColumn::make('spotbeam')
                    ->label('spotbeam')
                    ->hidden(),

                Tables\Columns\TextColumn::make('power_source')
                    ->label('power_source')
                    ->hidden(),

                Tables\Columns\TextColumn::make('pic_number')
                    ->label('pic_number')
                    ->hidden(),

                Tables\Columns\TextColumn::make('pic_name')
                    ->label('pic_name')
                    ->hidden(),
                // // Data dari Devices
                // Tables\Columns\TextColumn::make('devices.modem_type')
                //     ->label('Modem SN')
                //     ->sortable()
                //     ->searchable()
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('province')
                    ->label("Province")
                    ->native(false)
                    ->multiple()
                    ->options(fn() => SiteDetail::query()->pluck('province', 'province')),

                Tables\Filters\SelectFilter::make('area')
                    ->label("Area")
                    ->options(fn() => AreaList::all()->pluck('area', 'area'))
                    ->native(false)
                    ->modifyQueryUsing(function (Builder $query, $state) {
                        if (! $state['value']) {
                            return $query;
                        }
                        return $query->whereHas('area', fn($query) => $query->where('area', $state['value']));
                    }),

                Tables\Filters\SelectFilter::make('gateway')
                    ->label("Gateway")
                    ->native(false)
                    ->options(fn() => SiteDetail::query()->pluck('gateway', 'gateway')),

                Tables\Filters\SelectFilter::make('spotbeam')
                    ->label("Spotbeam")
                    ->native(false)
                    ->options(fn() => SiteDetail::query()->pluck('spotbeam', 'spotbeam')),
            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('site_name', 'asc')
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label("Details")
                    ->modalHeading("Site Detail"),
                Tables\Actions\DeleteAction::make(),
            ])
            ->poll('20s')
            ->deferLoading()
            ->paginated([10, 25, 50, 100, 500])
            ->defaultPaginationPageOption(10);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSiteDetails::route('/'),
        ];
    }
}
