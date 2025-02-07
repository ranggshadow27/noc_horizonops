<?php

namespace App\Filament\Resources\SiteMonitorResource\Pages;

use App\Filament\Resources\SiteMonitorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSiteMonitor extends EditRecord
{
    protected static string $resource = SiteMonitorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
