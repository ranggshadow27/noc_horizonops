<?php

namespace App\Observers;

use App\Models\NmtTickets;
use App\Models\SiteDetail;
use Carbon\Carbon;

class NmtTicketObserver
{
    /**
     * Handle the NmtTickets "created" event.
     */
    public function created(NmtTickets $nmtTickets): void
    {
        if ($nmtTickets->site_id) {
            // Ambil data dari site_details
            $site = SiteDetail::find($nmtTickets->site_id);

            if ($site) {
                // Update field di tmo_data
                $nmtTickets->update([
                    'site_province' => ucwords($site->province),
                ]);
            }
        }

        // if ($nmtTickets->status == "CLOSED") {
        //     $nmtTickets->update([
        //         'closed_date' => Carbon::parse(now())->format('Y-m-d H:i:s')
        //     ]);
        // }
    }

    /**
     * Handle the NmtTickets "updated" event.
     */
    public function updated(NmtTickets $nmtTickets): void
    {
        // if ($nmtTickets->status == "CLOSED") {
        //     $nmtTickets->update([
        //         'closed_date' => Carbon::parse(now())->format('Y-m-d H:i:s')
        //     ]);
        // }
    }

    /**
     * Handle the NmtTickets "deleted" event.
     */
    public function deleted(NmtTickets $nmtTickets): void
    {
        //
    }

    /**
     * Handle the NmtTickets "restored" event.
     */
    public function restored(NmtTickets $nmtTickets): void
    {
        //
    }

    /**
     * Handle the NmtTickets "force deleted" event.
     */
    public function forceDeleted(NmtTickets $nmtTickets): void
    {
        //
    }
}
