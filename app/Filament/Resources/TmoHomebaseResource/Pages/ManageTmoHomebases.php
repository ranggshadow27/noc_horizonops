<?php

namespace App\Filament\Resources\TmoHomebaseResource\Pages;

use App\Filament\Resources\TmoHomebaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTmoHomebases extends ManageRecords
{
    protected static string $resource = TmoHomebaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
