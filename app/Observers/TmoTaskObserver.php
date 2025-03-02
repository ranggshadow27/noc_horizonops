<?php

namespace App\Observers;

use App\Models\TmoData;
use App\Models\TmoImage;
use App\Models\TmoTask;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class TmoTaskObserver
{
    /**
     * Handle the TmoTask "created" event.
     */
    public function created(TmoTask $task): void
    {
        $engineer = User::where('name', $task->engineer)->first();
        $currentUser = auth()->user()->name;

        Notification::make()
            ->title('New TMO : ' . $task->tmo_id)
            ->warning()
            ->body(
                "{$task->site_id} - {$task->site_name}<br><br>
                SPMK : <strong>{$task->spmk_number}</strong>
                Assign by : <strong>{$currentUser}</strong>"
            )
            ->actions([
                Action::make('progress')
                    ->link()
                    ->markAsRead()
                    ->label('Progress Task')
                    ->icon('phosphor-hand-withdraw-duotone')
                    ->url(route('filament.mahaga.resources.t-m-o-datas.edit', $task->tmo_id), true)
                    ->openUrlInNewTab(false) // Redirect ke halaman edit
            ])
            ->sendToDatabase($engineer);

        Notification::make()
            ->title('Task Assign')
            ->success()
            ->body("The Task has been successfully assign")
            ->send();
    }

    public function creating(TmoTask $task)
    {
        // Buat data TmoData, tmo_id akan auto-generate di model
        $tmoData = TmoData::create([
            'site_id'  => $task->site_id,
            'site_name' => $task->site_name,
            'site_province' => $task->province,
            'site_address'  => $task->address,
            'engineer_name' => $task->engineer,
            'engineer_number' => $task->engineer_number,
            'tmo_type' => $task->tmo_type,
            'site_latitude' => $task->latitude,
            'site_longitude' => $task->longitude,
            'spmk_number' => $task->spmk_number,
            'is_device_change' => NULL,
            'created_by' => Auth::id(),
        ]);

        TmoImage::create([
            'tmo_id' => $tmoData->tmo_id,
        ]);

        // Ambil tmo_id yang baru dibuat dan set ke task
        $task->tmo_id = $tmoData->tmo_id;
        $task->created_by = auth()->id();
    }

    /**
     * Handle the TmoTask "updated" event.
     */
    public function updated(TmoTask $tmoTask): void
    {
        TmoData::where('tmo_id', $tmoTask->tmo_id)->update([
            'site_id'  => $tmoTask->site_id,
            'site_name' => $tmoTask->site_name,
            'site_province' => $tmoTask->province,
            'site_address'  => $tmoTask->address,
            'engineer_name' => $tmoTask->engineer,
            'engineer_number' => $tmoTask->engineer_number,
            'tmo_type' => $tmoTask->tmo_type,
            'site_latitude' => $tmoTask->latitude,
            'site_longitude' => $tmoTask->longitude,
            'spmk_number' => $tmoTask->spmk_number,
        ]);
    }

    /**
     * Handle the TmoTask "deleted" event.
     */
    public function deleted(TmoTask $tmoTask): void
    {
        TmoData::where('tmo_id', $tmoTask->tmo_id)->delete();
    }

    public function deleting(TmoTask $tmoTask)
    {
        //
    }

    /**
     * Handle the TmoTask "restored" event.
     */
    public function restored(TmoTask $tmoTask): void
    {
        //
    }

    /**
     * Handle the TmoTask "force deleted" event.
     */
    public function forceDeleted(TmoTask $tmoTask): void
    {
        //
    }
}
