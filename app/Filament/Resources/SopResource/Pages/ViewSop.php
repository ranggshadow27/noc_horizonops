<?php

namespace App\Filament\Resources\SopResource\Pages;

use App\Filament\Resources\SopResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\ViewRecord;

class ViewSop extends ViewRecord
{
    protected static string $resource = SopResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function getViewData(): array
    {

        // dd($this->record);

        return [
            'record' => $this->record,
        ];
    }

    protected static string $view = 'filament.resources.sop-resource.pages.view-sop';
}
