<?php

namespace App\Filament\Resources\CbossTicketResource\Pages;

use App\Filament\Resources\CbossTicketResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCbossTicket extends EditRecord
{
    protected static string $resource = CbossTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
