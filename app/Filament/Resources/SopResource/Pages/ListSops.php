<?php

namespace App\Filament\Resources\SopResource\Pages;

use App\Filament\Resources\SopResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSops extends ListRecords
{
    protected static string $resource = SopResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
