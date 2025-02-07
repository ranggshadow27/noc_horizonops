<?php

namespace App\Filament\Resources\SiteDetailResource\Pages;

use App\Filament\Resources\SiteDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSiteDetail extends EditRecord
{
    protected static string $resource = SiteDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
