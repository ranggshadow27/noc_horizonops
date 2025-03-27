<?php

namespace App\Filament\Resources\SweepingTicketResource\Pages;

use App\Filament\Resources\SweepingTicketResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSweepingTicket extends EditRecord
{
    protected static string $resource = SweepingTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
