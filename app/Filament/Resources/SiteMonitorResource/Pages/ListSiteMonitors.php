<?php

namespace App\Filament\Resources\SiteMonitorResource\Pages;

use App\Filament\Resources\SiteMonitorResource;
use App\Filament\Resources\SiteMonitorResource\Widgets\SiteMonitorOverview;
use App\Models\SiteDetail;
use App\Models\SiteMonitor;
use Filament\Actions;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListSiteMonitors extends ListRecords
{
    protected static string $resource = SiteMonitorResource::class;

    protected static ?string $title = 'Monitoring Site';

    public function getTabs(): array
    {
        return [
            'Show All' => Tab::make(),
            'normal' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', '=', 'normal'))
                ->badge(SiteMonitor::query()->where('status', '=', 'normal')->count())
                ->badgeColor('success'),
            'minor' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', '=', 'minor'))
                ->badge(SiteMonitor::query()->where('status', '=', 'minor')->count())
                ->badgeColor('primary'),
            'major' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', '=', 'major'))
                ->badge(SiteMonitor::query()->where('status', '=', 'major')->count())
                ->badgeColor('warning'),
            'critical' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', '=', 'critical'))
                ->badge(SiteMonitor::query()->where('status', '=', 'critical')->count())
                ->badgeColor('danger'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SiteMonitorOverview::class
        ];
    }

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
                            ->withFilename(fn($resource) => $resource::getModelLabel() . '-' . date('Y-m-d'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::CSV)
                            ->withColumns([
                                Column::make('updated_at'),
                            ])
                    ]),
                ExportAction::make('xlsx')
                    ->icon('phosphor-file-xls-duotone')
                    ->label("Export to XLSX")
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename(fn($resource) => $resource::getModelLabel() . '-' . date('Y-m-d'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->withColumns([
                                Column::make('modem_last_up'),
                                Column::make('mikrotik_last_up '),
                                Column::make('ap1_last_up '),
                                Column::make('ap2_last_up '),
                                Column::make('updated_at'),
                            ])
                    ]),
                // ExportAction::make('pdf')
                //     ->label("Export to PDF")
                //     ->icon('phosphor-file-pdf-duotone')
                //     ->openUrlInNewTab()
                //     ->action(function () {
                //         $records = SiteMonitor::all();
                //         $now = now()->format('d-m-Y');
                //         $filename = 'tickets_export_' . $now . '.pdf';
                //         return response()->streamDownload(function () use ($records) {
                //             echo Pdf::loadHTML(
                //                 Blade::render('bulk-pdf', ['records' => $records])
                //             )->stream();
                //         }, $filename);
                //     }),
            ])
                ->icon('heroicon-m-arrow-down-tray')
                ->label("Export Data")
                ->tooltip("Export Data"),
        ];
    }
}
