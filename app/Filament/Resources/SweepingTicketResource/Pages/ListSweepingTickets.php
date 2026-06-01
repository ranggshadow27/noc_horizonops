<?php

namespace App\Filament\Resources\SweepingTicketResource\Pages;

use App\Filament\Resources\SweepingTicketResource;
use App\Models\BroadcastSession;
use Filament\Actions;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\{Radio, Select, Textarea, Grid};
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListSweepingTickets extends ListRecords
{
    protected static string $resource = SweepingTicketResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Show All'), // Menampilkan total semua ticket

            'major' => Tab::make('Major')
                ->icon('phosphor-warning-duotone') // Opsional: tambah ikon
                ->modifyQueryUsing(fn(Builder $query) => $query->where('classification', 'MAJOR')->where('status', '!=', 'CLOSED'))
                ->badge($this->getModel()::query()->where('classification', 'MAJOR')->count())
                ->badgeColor('danger'),

            'minor' => Tab::make('Minor')
                ->icon('phosphor-warning-circle-duotone')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('classification', 'MINOR')->where('status', '!=', 'CLOSED'))
                ->badge($this->getModel()::query()->where('classification', 'MINOR')->count())
                ->badgeColor('warning'),

            'warning' => Tab::make('Warning')
                ->icon('phosphor-bell-duotone')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('classification', 'WARNING'))
                ->badge($this->getModel()::query()->where('classification', 'WARNING')->count())
                ->badgeColor('gray'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
            ActionGroup::make([
                ExportAction::make('csv')
                    ->icon('phosphor-file-csv-duotone')
                    ->label("Export to CSV")
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            // ->withFilename(fn($resource) => $resource::getModelLabel() . '-' . date('Y-m-d'))
                            ->withFilename('Sweeping TT Export_' . date('ymd'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::CSV)
                            ->modifyQueryUsing(function (Builder $query) {
                                if (auth()->user()->roles->pluck('id')->contains(4)) {
                                    return $query->where('created_by', auth()->id());
                                }

                                // if (auth()->user()->roles->pluck('id')->some(fn($id) => $id > 4)) {
                                //     return $query->where('engineer_name', auth()->user()->name);
                                // }
                            })
                    ]),

                ExportAction::make('xlsx')
                    ->icon('phosphor-file-xls-duotone')
                    ->label("Export to XLSX")
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            // ->withFilename(fn($resource) => $resource::getModelLabel() . '-' . date('Y-m-d'))
                            ->withFilename('Sweeping TT Export_' . date('ymd'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->modifyQueryUsing(function (Builder $query) {
                                if (auth()->user()->roles->pluck('id')->some(fn($id) => $id < 4)) {
                                    return $query;
                                }

                                // if (auth()->user()->roles->pluck('id')->contains(4)) {
                                //     return $query->where('created_by', auth()->id());
                                // }

                                // if (auth()->user()->roles->pluck('id')->some(fn($id) => $id > 4)) {
                                //     return $query->where('engineer_name', auth()->user()->name);
                                // }
                            })
                    ]),
            ])->icon('heroicon-m-arrow-down-tray')
                ->label("Export Data")
                ->tooltip("Export Data"),

            Actions\Action::make('fetch_sweeping_tickets')
                ->label('Get Data')
                // ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    // Jalankan command artisan
                    Artisan::call('fetch:sweeping-tickets');

                    // Tampilkan output command di notifikasi
                    $output = Artisan::output();
                    \Filament\Notifications\Notification::make()
                        ->title('Fetch Completed')
                        ->body($output)
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Confirm to Fetch Data')
                ->modalDescription('Are you sure to Fetch data from Gsheet?.'),

            Actions\Action::make('live_broadcast')
                ->label('Broadcast Management')
                ->icon('phosphor-gear-duotone')
                ->color('gray')
                ->button()
                ->modalHeading('Whatsapp Broadcast Management')
                ->modalWidth('7xl')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->form([
                    \Filament\Forms\Components\View::make('filament.pages.broadcast-monitor')
                        ->viewData([
                            'sessions' => $this->getActiveSessions()   // pakai $this karena ini di Page
                        ])
                ])
                ->action(fn() => null),   // tidak perlu action
        ];
    }

    public function pauseSession($id)
    {
        BroadcastSession::where('id', $id)->update(['status' => 'paused']);
        $this->js('window.location.reload()'); // refresh modal
    }

    public function resumeSession($id)
    {
        BroadcastSession::where('id', $id)->update(['status' => 'active']);
        $this->js('window.location.reload()');
    }

    public function stopSession($id)
    {
        BroadcastSession::where('id', $id)->update([
            'status' => 'stopped',
            'completed_at' => now()
        ]);
        $this->js('window.location.reload()');
    }

    public function getActiveSessions()
    {
        return BroadcastSession::withCount([
            'logs as sent_count' => fn($query) => $query->whereNot('status', 'pending'),
            'logs as failed_count' => fn($query) => $query->where('status', 'failed'),
        ])
            ->whereIn('status', ['active', 'paused'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($session) {
                $session->is_expired = $session->started_at
                    ? Carbon::parse($session->started_at)->diffInHours(now()) >= 24
                    : false;
                return $session;
            });
    }
}
