<?php

namespace App\Filament\Resources\TMODataResource\Pages;

use App\Filament\Resources\TMODataResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTMOData extends ListRecords
{
    protected static string $resource = TMODataResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
