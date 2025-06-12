<?php

namespace App\Filament\Resources\NmtTicketsResource\Pages;

use App\Filament\Resources\NmtTicketsResource;
use App\Filament\Resources\NmtTicketsResource\Widgets\NmtTicketsResourceOverview;
use App\Filament\Resources\NmtTicketsResource\Widgets\NmtTTResourceOverview;
use App\Models\NmtTickets;
use App\Models\SiteMonitor;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use Illuminate\Support\Str;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Actions\ActionGroup;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Artisan;
use Webbingbrasil\FilamentCopyActions\Pages\Actions\CopyAction;

class ListNmtTickets extends ListRecords
{
    protected static string $resource = NmtTicketsResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            NmtTicketsResourceOverview::class
        ];
    }

    public function getTabs(): array
    {
        return [
            'Show All' => Tab::make(),

            'Online' => Tab::make()
                ->modifyQueryUsing(fn(NmtTickets $data) => $data
                    ->join('site_monitor', 'nmt_tickets.site_id', '=', 'site_monitor.site_id')
                    ->where('site_monitor.sensor_status', 'Online')
                    ->where('nmt_tickets.status', 'OPEN'))
                ->badge(NmtTickets::query()
                    ->join('site_monitor', 'nmt_tickets.site_id', '=', 'site_monitor.site_id')
                    ->where('site_monitor.sensor_status', 'Online')
                    ->where('nmt_tickets.status', 'OPEN')
                    ->count())
                ->badgeColor('success'),

            'All Sensor Down' => Tab::make()
                ->modifyQueryUsing(fn(NmtTickets $data) => $data
                    ->join('site_monitor', 'nmt_tickets.site_id', '=', 'site_monitor.site_id')
                    ->where('site_monitor.sensor_status', 'All Sensor Down')
                    ->where('nmt_tickets.status', 'OPEN'))
                ->badge(NmtTickets::query()
                    ->join('site_monitor', 'nmt_tickets.site_id', '=', 'site_monitor.site_id')
                    ->where('site_monitor.sensor_status', 'All Sensor Down')
                    ->where('nmt_tickets.status', 'OPEN')
                    ->count())
                ->badgeColor('danger'),

            'Non Modem Down' => Tab::make()
                ->modifyQueryUsing(fn(NmtTickets $data) => $data
                    ->join('site_monitor', 'nmt_tickets.site_id', '=', 'site_monitor.site_id')
                    ->whereNot('site_monitor.sensor_status', 'All Sensor Down')
                    ->where('nmt_tickets.status', 'OPEN'))
                ->badge(NmtTickets::query()
                    ->join('site_monitor', 'nmt_tickets.site_id', '=', 'site_monitor.site_id')
                    ->whereNot('site_monitor.sensor_status', 'All Sensor Down')
                    ->where('nmt_tickets.status', 'OPEN')
                    ->count())
                ->badgeColor('warning'),

            'Router Down' => Tab::make()
                ->modifyQueryUsing(fn(NmtTickets $data) => $data
                    ->join('site_monitor', 'nmt_tickets.site_id', '=', 'site_monitor.site_id')
                    ->where('site_monitor.sensor_status', 'Router Down')
                    ->where('nmt_tickets.status', 'OPEN'))
                ->badge(NmtTickets::query()
                    ->join('site_monitor', 'nmt_tickets.site_id', '=', 'site_monitor.site_id')
                    ->where('site_monitor.sensor_status', 'Router Down')
                    ->where('nmt_tickets.status', 'OPEN')
                    ->count())
                ->badgeColor('warning'),

            'AP1&2 Down' => Tab::make()
                ->modifyQueryUsing(fn(NmtTickets $data) => $data
                    ->join('site_monitor', 'nmt_tickets.site_id', '=', 'site_monitor.site_id')
                    ->where('site_monitor.sensor_status', 'AP1&2 Down')
                    ->where('nmt_tickets.status', 'OPEN'))
                ->badge(NmtTickets::query()
                    ->join('site_monitor', 'nmt_tickets.site_id', '=', 'site_monitor.site_id')
                    ->where('site_monitor.sensor_status', 'AP1&2 Down')
                    ->where('nmt_tickets.status', 'OPEN')
                    ->count())
                ->badgeColor('warning'),

            'AP1 Down' => Tab::make()
                ->modifyQueryUsing(fn(NmtTickets $data) => $data
                    ->join('site_monitor', 'nmt_tickets.site_id', '=', 'site_monitor.site_id')
                    ->where('site_monitor.sensor_status', 'AP1 Down')
                    ->where('nmt_tickets.status', 'OPEN'))
                ->badge(NmtTickets::query()
                    ->join('site_monitor', 'nmt_tickets.site_id', '=', 'site_monitor.site_id')
                    ->where('site_monitor.sensor_status', 'AP1 Down')
                    ->where('nmt_tickets.status', 'OPEN')
                    ->count())
                ->badgeColor('apricot'),

            'AP2 Down' => Tab::make()
                ->modifyQueryUsing(fn(NmtTickets $data) => $data
                    ->join('site_monitor', 'nmt_tickets.site_id', '=', 'site_monitor.site_id')
                    ->where('site_monitor.sensor_status', 'AP2 Down')
                    ->where('nmt_tickets.status', 'OPEN'))
                ->badge(NmtTickets::query()
                    ->join('site_monitor', 'nmt_tickets.site_id', '=', 'site_monitor.site_id')
                    ->where('site_monitor.sensor_status', 'AP2 Down')
                    ->where('nmt_tickets.status', 'OPEN')
                    ->count())
                ->badgeColor('apricot'),
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
                            // ->withFilename(fn($resource) => $resource::getModelLabel() . '-' . date('Y-m-d'))
                            ->withFilename('NMT Ticket Export_' . date('ymd'))
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
                            ->withFilename('NMT Ticket Export_' . date('ymd'))
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
