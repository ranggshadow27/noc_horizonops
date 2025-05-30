<?php

namespace App\Filament\Resources\TmoTaskResource\Pages;

use App\Filament\Resources\TmoTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTmoTask extends CreateRecord
{
    protected static string $resource = TmoTaskResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
