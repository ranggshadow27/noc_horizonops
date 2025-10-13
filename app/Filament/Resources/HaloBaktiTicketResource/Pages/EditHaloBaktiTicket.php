<?php

namespace App\Filament\Resources\HaloBaktiTicketResource\Pages;

use App\Filament\Resources\HaloBaktiTicketResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHaloBaktiTicket extends EditRecord
{
    protected static string $resource = HaloBaktiTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            // Actions\ForceDeleteAction::make(),
            // Actions\RestoreAction::make(),
        ];
    }
}
