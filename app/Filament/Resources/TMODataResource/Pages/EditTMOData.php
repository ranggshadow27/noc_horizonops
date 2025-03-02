<?php

namespace App\Filament\Resources\TMODataResource\Pages;

use App\Filament\Resources\TMODataResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTMOData extends EditRecord
{
    protected static string $resource = TMODataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
