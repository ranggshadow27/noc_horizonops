<?php

namespace App\Filament\Resources\ChatTemplateResource\Pages;

use App\Filament\Resources\ChatTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageChatTemplates extends ManageRecords
{
    protected static string $resource = ChatTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
