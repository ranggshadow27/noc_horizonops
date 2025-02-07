<?php

namespace App\Observers;

use App\Models\TMOImage;
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
    public function created(TMOImage $tMOImage): void
    {
        //
    }

    /**
     * Handle the TMOImage "updated" event.
     */

    /**
     * Handle the TMOImage "deleted" event.
     */
    public function deleted(TMOImage $tmoImage): void
    {
        //
    }

    public function deleting(TMOImage $tmoImage)
    {
        foreach ($this->imageFields as $field) {
            if ($tmoImage->$field) {
                Storage::disk('public')->delete($tmoImage->$field);
            }
        }
    }

    public function updated(TMOImage $tmoImage)
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
    public function restored(TMOImage $tMOImage): void
    {
        //
    }

    /**
     * Handle the TMOImage "force deleted" event.
     */
    public function forceDeleted(TMOImage $tMOImage): void
    {
        //
    }
}
