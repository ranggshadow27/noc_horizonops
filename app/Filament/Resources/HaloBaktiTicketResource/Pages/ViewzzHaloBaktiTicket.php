<?php

namespace App\Filament\Resources\HaloBaktiTicketResource\Pages;

use App\Filament\Resources\HaloBaktiTicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewzzHaloBaktiTicket extends ViewRecord
{
    protected static string $resource = HaloBaktiTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
