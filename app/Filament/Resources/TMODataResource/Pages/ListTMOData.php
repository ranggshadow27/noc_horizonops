<?php

namespace App\Filament\Resources\TMODataResource\Pages;

use App\Filament\Resources\TMODataResource;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;

class ListTMOData extends ListRecords
{
    protected static string $resource = TMODataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ExportAction::make('csv')
                    ->icon('phosphor-file-csv-duotone')
                    ->label("Export to CSV")
                    ->exports([
                        ExcelExport::make()
                            // ->fromTable()
                            // ->withFilename(fn($resource) => $resource::getModelLabel() . '-' . date('Y-m-d'))
                            ->withFilename('TMO Data Export_' . date('ymd'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::CSV)
                            ->withColumns([
                                Column::make('tmo_id')
                                    ->heading('TMO ID'),

                                Column::make('cboss_tmo_code')
                                    ->heading('CBOSS TMO'),

                                Column::make('approval')
                                    ->heading('Status'),

                                Column::make('tmo_type')
                                    ->heading('TMO Type'),

                                Column::make('site_id')
                                    ->heading('Site ID'),

                                Column::make('site_name')
                                    ->heading('Site Name'),

                                Column::make('site_province')
                                    ->heading('Province'),

                                Column::make('site_address')
                                    ->heading('Address'),

                                Column::make('site_latitude')
                                    ->heading('Lat / Long')
                                    ->formatStateUsing(fn($record) => $record->site_latitude . " / " . $record->site_longitude),

                                Column::make('engineer_name')
                                    ->heading('Engineer Onsite')
                                    ->formatStateUsing(fn($record) => $record->engineer_name . " / " . $record->engineer_number),

                                Column::make('pic_name')
                                    ->heading('PIC')
                                    ->formatStateUsing(fn($record) => $record->pic_name . " / " . $record->pic_number),

                                Column::make('tmo_start_date')
                                    ->heading('TMO Start Date')
                                    ->formatStateUsing(fn($state) => Carbon::parse($state)->translatedFormat('d M Y H:i')),

                                Column::make('tmo_end_date')
                                    ->heading('TMO End Date')
                                    ->formatStateUsing(fn($state) => Carbon::parse($state)->translatedFormat('d M Y H:i')),

                                Column::make('problem_json')
                                    ->heading('Site Problems'),
                                Column::make('action_json')
                                    ->heading('Engineer Actions'),
                                Column::make('updated_at'),

                                Column::make('tmoDetail.transceiver_sn')
                                    ->heading('Transceiver SN'),
                                Column::make('tmoDetail.transceiver_type')
                                    ->heading('Transceiver Type'),
                                Column::make('tmoDetail.feedhorn_sn')
                                    ->heading('Feedhorn SN'),
                                Column::make('tmoDetail.antenna_sn')
                                    ->heading('Dish Antenna SN'),
                                Column::make('tmoDetail.stabillizer_sn')
                                    ->heading('Stabillizer SN'),
                                Column::make('tmoDetail.modem_type')
                                    ->heading('Modem Type'),
                                Column::make('tmoDetail.modem_sn')
                                    ->heading('Modem SN'),
                                Column::make('tmoDetail.router_type')
                                    ->heading('Router Type'),
                                Column::make('tmoDetail.router_sn')
                                    ->heading('Router SN'),
                                Column::make('tmoDetail.ap1_type')
                                    ->heading('AP 1 Type'),
                                Column::make('tmoDetail.ap1_sn')
                                    ->heading('AP 1 SN'),
                                Column::make('tmoDetail.ap2_type')
                                    ->heading('AP 2 Type'),
                                Column::make('tmoDetail.ap2_sn')
                                    ->heading('AP 2 SN'),
                                Column::make('tmoDetail.ap2_sn')
                                    ->heading('Rack Indoor SN'),

                                Column::make('deviceChanges')
                                    ->heading('Fail Device & Homebase')
                                    ->formatStateUsing(
                                        fn($record) =>
                                        $record->load('deviceChanges.homebase')
                                            ->deviceChanges
                                            ->map(
                                                fn($deviceChange) =>
                                                $deviceChange->device_name . " (to HB: " . ($deviceChange->homebase->location ?? '-') . ")"
                                            )
                                            ->implode(", ")
                                    ),
                            ])
                            ->modifyQueryUsing(function (Builder $query) {
                                if (auth()->user()->roles->pluck('name')->contains('panel_user')) {
                                    return $query->where('engineer_name', auth()->user()->name);
                                }
                            })
                    ]),

                ExportAction::make('xlsx')
                    ->icon('phosphor-file-xls-duotone')
                    ->label("Export to XLSX")
                    ->exports([
                        ExcelExport::make()
                            // ->fromTable()
                            // ->withFilename(fn($resource) => $resource::getModelLabel() . '-' . date('Y-m-d'))
                            ->withFilename('TMO Data Export_' . date('ymd'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->withColumns([
                                Column::make('tmo_id')
                                    ->heading('TMO ID'),

                                Column::make('cboss_tmo_code')
                                    ->heading('CBOSS TMO'),

                                Column::make('approval')
                                    ->heading('Status'),

                                Column::make('tmo_type')
                                    ->heading('TMO Type'),

                                Column::make('site_id')
                                    ->heading('Site ID'),

                                Column::make('site_name')
                                    ->heading('Site Name'),

                                Column::make('site_province')
                                    ->heading('Province'),

                                Column::make('site_address')
                                    ->heading('Address'),

                                Column::make('site_latitude')
                                    ->heading('Lat / Long')
                                    ->formatStateUsing(fn($record) => $record->site_latitude . " / " . $record->site_longitude),

                                Column::make('engineer_name')
                                    ->heading('Engineer Onsite')
                                    ->formatStateUsing(fn($record) => $record->engineer_name . " / " . $record->engineer_number),

                                Column::make('pic_name')
                                    ->heading('PIC')
                                    ->formatStateUsing(fn($record) => $record->pic_name . " / " . $record->pic_number),

                                Column::make('tmo_start_date')
                                    ->heading('TMO Start Date')
                                    ->formatStateUsing(fn($state) => Carbon::parse($state)->translatedFormat('d M Y H:i')),

                                Column::make('tmo_end_date')
                                    ->heading('TMO End Date')
                                    ->formatStateUsing(fn($state) => Carbon::parse($state)->translatedFormat('d M Y H:i')),

                                Column::make('problem_json')
                                    ->heading('Site Problems'),
                                Column::make('action_json')
                                    ->heading('Engineer Actions'),
                                Column::make('updated_at'),

                                Column::make('tmoDetail.transceiver_sn')
                                    ->heading('Transceiver SN'),
                                Column::make('tmoDetail.transceiver_type')
                                    ->heading('Transceiver Type'),
                                Column::make('tmoDetail.feedhorn_sn')
                                    ->heading('Feedhorn SN'),
                                Column::make('tmoDetail.antenna_sn')
                                    ->heading('Dish Antenna SN'),
                                Column::make('tmoDetail.stabillizer_sn')
                                    ->heading('Stabillizer SN'),
                                Column::make('tmoDetail.modem_type')
                                    ->heading('Modem Type'),
                                Column::make('tmoDetail.modem_sn')
                                    ->heading('Modem SN'),
                                Column::make('tmoDetail.router_type')
                                    ->heading('Router Type'),
                                Column::make('tmoDetail.router_sn')
                                    ->heading('Router SN'),
                                Column::make('tmoDetail.ap1_type')
                                    ->heading('AP 1 Type'),
                                Column::make('tmoDetail.ap1_sn')
                                    ->heading('AP 1 SN'),
                                Column::make('tmoDetail.ap2_type')
                                    ->heading('AP 2 Type'),
                                Column::make('tmoDetail.ap2_sn')
                                    ->heading('AP 2 SN'),
                                Column::make('tmoDetail.ap2_sn')
                                    ->heading('Rack Indoor SN'),

                                Column::make('deviceChanges')
                                    ->heading('Fail Device & Homebase')
                                    ->formatStateUsing(
                                        fn($record) =>
                                        $record->load('deviceChanges.homebase')
                                            ->deviceChanges
                                            ->map(
                                                fn($deviceChange) =>
                                                $deviceChange->device_name . " (to HB: " . ($deviceChange->homebase->location ?? '-') . ")"
                                            )
                                            ->implode(", ")
                                    ),
                            ])
                            ->modifyQueryUsing(function (Builder $query) {
                                if (auth()->user()->roles->pluck('name')->contains('panel_user')) {
                                    return $query->where('engineer_name', auth()->user()->name);
                                } else {
                                    return $query;
                                }
                            })
                    ]),

            ])
                ->icon('heroicon-m-arrow-down-tray')
                ->label("Export Data")
                ->tooltip("Export Data"),
        ];
    }
}
