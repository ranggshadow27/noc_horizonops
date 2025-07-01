<?php

namespace App\Filament\Resources\SiteDetailResource\Pages;

use App\Filament\Resources\SiteDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Actions\ActionGroup;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;

class ListSiteDetails extends ListRecords
{
    protected static string $resource = SiteDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->button()
                ->label("Add New Site")
                ->icon('phosphor-plus-circle-duotone'),

            ActionGroup::make([
                ExportAction::make('csv')
                    ->icon('phosphor-file-csv-duotone')
                    ->label("Export to CSV")
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            // ->withFilename(fn($resource) => $resource::getModelLabel() . '-' . date('Y-m-d'))
                            ->withFilename('Site Detail Export_' . date('ymd'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::CSV)
                            ->modifyQueryUsing(function (Builder $query) {
                                if (auth()->user()->roles->pluck('id')->contains(4)) {
                                    return $query->where('created_by', auth()->id());
                                }

                                if (auth()->user()->roles->pluck('id')->some(fn($id) => $id > 4)) {
                                    return $query->where('engineer_name', auth()->user()->name);
                                }
                            })
                    ]),


                ExportAction::make('xlsx')
                    ->icon('phosphor-file-xls-duotone')
                    ->label("Export to XLSX")
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            // ->withFilename(fn($resource) => $resource::getModelLabel() . '-' . date('Y-m-d'))
                            ->withFilename('Site Detail Export_' . date('ymd'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->modifyQueryUsing(function (Builder $query) {
                                if (auth()->user()->roles->pluck('id')->some(fn($id) => $id < 4)) {
                                    return $query;
                                }

                                if (auth()->user()->roles->pluck('id')->contains(4)) {
                                    return $query->where('created_by', auth()->id());
                                }

                                if (auth()->user()->roles->pluck('id')->some(fn($id) => $id > 4)) {
                                    return $query->where('engineer_name', auth()->user()->name);
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
