<?php

namespace App\Observers;

use App\Models\TMOData;
use Illuminate\Support\Facades\Storage;

class TMODataObserver
{
    /**
     * Handle the TMOData "created" event.
     */
    public function created(TMOData $tMOData): void
    {
        //
    }

    /**
     * Handle the TMOData "updated" event.
     */
    public function updated(TMOData $tmoData)
    {
        if ($tmoData->is_device_change == false) {
            // Ambil semua data yang akan dihapus
            $deviceChanges = $tmoData->deviceChanges;

            foreach ($deviceChanges as $deviceChange) {
                if ($deviceChange->device_img) {
                    // Hapus file gambar di storage
                    Storage::disk('public')->delete($deviceChange->device_img);
                }
            }

            // Hapus data di tabel tmo_device_change
            $tmoData->deviceChanges()->delete();
        }
    }

    /**
     * Handle the TMOData "deleted" event.
     */
    public function deleted(TMOData $tMOData): void
    {
        //
    }

    /**
     * Handle the TMOData "restored" event.
     */
    public function restored(TMOData $tMOData): void
    {
        //
    }

    /**
     * Handle the TMOData "force deleted" event.
     */
    public function forceDeleted(TMOData $tMOData): void
    {
        //
    }
}
