<?php

namespace App\Filament\Resources\CbossTmoResource\Pages;

use App\Filament\Resources\CbossTmoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCbossTmos extends ListRecords
{
    protected static string $resource = CbossTmoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
