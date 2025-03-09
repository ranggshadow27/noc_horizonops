<?php

namespace App\Observers;

use App\Models\SiteDetail;
use App\Models\TmoData;
use App\Models\TmoTask;
use Illuminate\Support\Facades\Storage;

class TMODataObserver
{
    /**
     * Handle the TMOData "created" event.
     */
    public function created(TmoData $tmoData): void
    {
        if ($tmoData->site_id) {
            // Ambil data dari site_details
            $site = SiteDetail::find($tmoData->site_id);

            if ($site) {
                // Update field di tmo_data
                $tmoData->update([
                    'site_name' => $site->site_name,
                    'site_province' => $site->province,
                    'site_address' => $site->address,
                    'site_latitude' => $site->latitude,
                    'site_longitude' => $site->longitude,
                ]);
            }
        }
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

        TmoTask::where('tmo_id', $tmoData->tmo_id)->update([
            'site_id'  => $tmoData->site_id,
            'site_name' => $tmoData->site_name,
            'province' => $tmoData->site_province,
            'address'  => $tmoData->site_address,
            // 'engineer_name' => $tmoData->engineer,
            // 'engineer_number' => $tmoData->engineer_number,
            'tmo_type' => $tmoData->tmo_type,
            'latitude' => $tmoData->site_latitude,
            'longitude' => $tmoData->site_longitude,
            // 'spmk_number' => $tmoData->spmk_number,
        ]);
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

    public function saved(TmoData $tMOData): void {}

    /**
     * Handle the TMOData "force deleted" event.
     */
    public function forceDeleted(TMOData $tMOData): void
    {
        //
    }
}
