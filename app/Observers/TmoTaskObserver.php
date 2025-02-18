<?php

namespace App\Observers;

use App\Models\TmoData;
use App\Models\TmoTask;
use Illuminate\Support\Facades\Auth;

class TmoTaskObserver
{
    /**
     * Handle the TmoTask "created" event.
     */
    public function created(TmoTask $tmoTask): void
    {
        //
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
            'created_by' => Auth::id(),
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
        //
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
