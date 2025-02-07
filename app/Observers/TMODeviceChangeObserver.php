<?php

namespace App\Observers;

use App\Models\TMODeviceChange;
use Illuminate\Support\Facades\Storage;

class TMODeviceChangeObserver
{
    /**
     * Handle the TMODeviceChange "created" event.
     */
    public function created(TMODeviceChange $tMODeviceChange): void
    {
        //
    }

    /**
     * Handle the TMODeviceChange "updated" event.
     */
    public function updated(TMODeviceChange $tMODeviceChange): void
    {
        // Cek jika gambar baru diunggah
        if ($tMODeviceChange->isDirty('device_img')) { // Cek apakah gambar berubah
            $oldImage = $tMODeviceChange->getOriginal('device_img'); // Ambil gambar lama
            if ($oldImage) {
                Storage::disk('public')->delete($oldImage); // Hapus gambar lama
            }
        }

    }


    /**
     * Handle the TMODeviceChange "deleted" event.
     */
    public function deleted(TMODeviceChange $tMODeviceChange): void
    {
        if ($tMODeviceChange->device_img) {
            Storage::disk('public')->delete($tMODeviceChange->device_img);
        }
    }

    /**
     * Handle the TMODeviceChange "restored" event.
     */
    public function restored(TMODeviceChange $tMODeviceChange): void
    {
        //
    }

    /**
     * Handle the TMODeviceChange "force deleted" event.
     */
    public function forceDeleted(TMODeviceChange $tMODeviceChange): void
    {
        //
    }
}
