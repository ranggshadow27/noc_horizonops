<?php

namespace App\Filament\Resources\SweepingTicketResource\Pages;

use App\Filament\Resources\SweepingTicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;

class ListSweepingTickets extends ListRecords
{
    protected static string $resource = SweepingTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
            Actions\Action::make('fetch_sweeping_tickets')
                ->label('Fetch Data')
                ->icon('heroicon-o-arrow-down-tray')
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
                ->modalDescription('Are you sure to Fetch data from Gsheet?.')
        ];
    }
}
