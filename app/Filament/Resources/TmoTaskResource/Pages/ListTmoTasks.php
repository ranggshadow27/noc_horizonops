<?php

namespace App\Filament\Resources\TmoTaskResource\Pages;

use App\Filament\Resources\TmoTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTmoTasks extends ListRecords
{
    protected static string $resource = TmoTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
