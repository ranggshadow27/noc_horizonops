<?php

namespace App\Filament\Resources\TmoTaskResource\Pages;

use App\Filament\Resources\TmoTaskResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTmoTask extends EditRecord
{
    protected static string $resource = TmoTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Task deleted')
                        ->body('TMO Task has been deleted successfully.'),
                ),
        ];
    }
}
