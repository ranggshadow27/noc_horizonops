<?php

namespace App\Filament\Resources\HaloBaktiTicketResource\Pages;

use App\Filament\Resources\HaloBaktiTicketResource;
use App\Filament\Widgets\HaloBaktiTicketStats;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHaloBaktiTickets extends ListRecords
{
    protected static string $resource = HaloBaktiTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
    protected function getHeaderWidgets(): array
    {
        return [
            HaloBaktiTicketStats::class,
        ];
    }
}
