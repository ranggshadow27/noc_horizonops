<?php

namespace App\Filament\Resources\TmoProblemResource\Pages;

use App\Filament\Resources\TmoProblemResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTmoProblems extends ManageRecords
{
    protected static string $resource = TmoProblemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
