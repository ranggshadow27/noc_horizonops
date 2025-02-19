<?php

namespace App\Observers;

use App\Models\TmoDeviceChange;
use Illuminate\Support\Facades\Storage;

class TMODeviceChangeObserver
{
    /**
     * Handle the TMODeviceChange "created" event.
     */
    public function created(TmoDeviceChange $tMODeviceChange): void
    {
        //
    }

    /**
     * Handle the TMODeviceChange "updated" event.
     */
    public function updated(TmoDeviceChange $tMODeviceChange): void
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
    public function deleted(TmoDeviceChange $tMODeviceChange): void
    {
        if ($tMODeviceChange->device_img) {
            Storage::disk('public')->delete($tMODeviceChange->device_img);
        }
    }

    /**
     * Handle the TMODeviceChange "restored" event.
     */
    public function restored(TmoDeviceChange $tMODeviceChange): void
    {
        //
    }

    /**
     * Handle the TMODeviceChange "force deleted" event.
     */
    public function forceDeleted(TmoDeviceChange $tMODeviceChange): void
    {
        //
    }
}
