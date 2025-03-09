<?php

namespace App\Filament\Resources\NmtTicketsResource\Pages;

use App\Filament\Resources\NmtTicketsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNmtTickets extends EditRecord
{
    protected static string $resource = NmtTicketsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
