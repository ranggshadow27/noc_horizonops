<?php

namespace App\Filament\Resources\CbossTicketResource\Pages;

use App\Filament\Resources\CbossTicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ListCbossTickets extends ListRecords
{
    protected static string $resource = CbossTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ExportAction::make('csv')
                    ->icon('phosphor-file-csv-duotone')
                    ->label("Export to CSV")
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename('CBOSS Ticket Export_' . date('ymd'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::CSV)
                            ->withColumns([
                                Column::make('spmk')
                                    ->heading('No. SPMK'),

                                Column::make('site_id')
                                    ->heading('Site ID'),

                                Column::make('siteDetail.area.area')
                                    ->heading('Area'),

                                Column::make('status')
                                    ->heading('Status'),

                                Column::make('problem_map')
                                    ->heading('Problem Map'),

                                Column::make('detail_action')
                                    ->heading('Action'),

                                Column::make('ticket_last_update')
                                    ->heading('Last Update')
                                    ->formatStateUsing(fn($state) => Carbon::parse($state)->translatedFormat('d M Y H:i')),
                            ])
                    ]),

                ExportAction::make('xlsx')
                    ->icon('phosphor-file-xls-duotone')
                    ->label("Export to XLSX")
                    ->exports([
                        ExcelExport::make('cboss_tickets')
                            ->fromTable()
                            ->withFilename('CBOSS Data Export_' . date('ymd'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->withColumns([
                                Column::make('spmk')
                                    ->heading('No. SPMK'),

                                Column::make('site_id')
                                    ->heading('Site ID'),

                                Column::make('siteDetail.area.area')
                                    ->heading('Area'),

                                Column::make('status')
                                    ->heading('Status'),

                                Column::make('problem_map')
                                    ->heading('Problem Map'),

                                Column::make('detail_action')
                                    ->heading('Action'),

                                Column::make('ticket_last_update')
                                    ->heading('Last Update')
                                    ->formatStateUsing(fn($state) => Carbon::parse($state)->translatedFormat('d M Y H:i')),
                            ])
                    ]),
            ])
                ->icon('heroicon-m-arrow-down-tray')
                ->label("Export Data")
                ->tooltip("Export Data"),
        ];
    }
}
