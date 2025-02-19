<?php

namespace App\Observers;

use App\Models\TmoImage;
use Illuminate\Support\Facades\Storage;

class TMOImageObserver
{
    protected $imageFields = [
        'transceiver_img',
        'feedhorn_img',
        'antenna_img',
        'stabillizer_img',
        'rack_img',
        'modem_img',
        'router_img',
        'ap1_img',
        'ap2_img',
        'modem_summary_img',
        'pingtest_img',
        'speedtest_img',
        'cm_ba_img',
        'pm_ba_img',
        'signplace_img',
        'stabillizer_voltage_img',
        'power_source_voltage_img',
    ];

    /**
     * Handle the TMOImage "created" event.
     */
    public function created(TmoImage $tMOImage): void
    {
        //
    }

    /**
     * Handle the TMOImage "updated" event.
     */

    /**
     * Handle the TMOImage "deleted" event.
     */
    public function deleted(TmoImage $tmoImage): void
    {
        //
    }

    public function deleting(TmoImage $tmoImage)
    {
        foreach ($this->imageFields as $field) {
            if ($tmoImage->$field) {
                Storage::disk('public')->delete($tmoImage->$field);
            }
        }
    }

    public function updated(TmoImage $tmoImage)
    {
        foreach ($this->imageFields as $field) {
            if ($tmoImage->isDirty($field)) { // Cek apakah kolom ini berubah
                $oldImage = $tmoImage->getOriginal($field); // Ambil gambar lama
                if ($oldImage) {
                    Storage::disk('public')->delete($oldImage); // Hapus gambar lama
                }
            }
        }
    }

    /**
     * Handle the TMOImage "restored" event.
     */
    public function restored(TmoImage $tMOImage): void
    {
        //
    }

    /**
     * Handle the TMOImage "force deleted" event.
     */
    public function forceDeleted(TmoImage $tMOImage): void
    {
        //
    }
}
