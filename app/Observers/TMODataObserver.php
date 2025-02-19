<?php

namespace App\Observers;

use App\Models\TmoData;
use Illuminate\Support\Facades\Storage;

class TMODataObserver
{
    /**
     * Handle the TMOData "created" event.
     */
    public function created(TmoData $tMOData): void
    {
        //
    }

    /**
     * Handle the TMOData "updated" event.
     */
    public function updated(TmoData $tmoData)
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
    public function deleted(TmoData $tMOData): void
    {
        //
    }

    /**
     * Handle the TMOData "restored" event.
     */
    public function restored(TmoData $tMOData): void
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
