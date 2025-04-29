<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SweepingTicket;
use Illuminate\Auth\Access\HandlesAuthorization;

class SweepingTicketPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_sweeping::ticket');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SweepingTicket $sweepingTicket): bool
    {
        return $user->can('view_sweeping::ticket');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_sweeping::ticket');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SweepingTicket $sweepingTicket): bool
    {
        return $user->can('update_sweeping::ticket');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SweepingTicket $sweepingTicket): bool
    {
        return $user->can('delete_sweeping::ticket');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_sweeping::ticket');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, SweepingTicket $sweepingTicket): bool
    {
        return $user->can('force_delete_sweeping::ticket');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_sweeping::ticket');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, SweepingTicket $sweepingTicket): bool
    {
        return $user->can('restore_sweeping::ticket');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_sweeping::ticket');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, SweepingTicket $sweepingTicket): bool
    {
        return $user->can('replicate_sweeping::ticket');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_sweeping::ticket');
    }
}
