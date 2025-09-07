<?php

namespace App\Policies;

use App\Enum\InvoiceStatus;
use App\Models\Admin;
use App\Models\Invoice;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     * This allows a super-admin to bypass all other policy checks.
     */
    public function before(Admin $admin, string $ability): ?bool
    {
        if ($admin->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can update the status of the invoice.
     * This is the gatekeeper for our state machine.
     */
    public function updateStatus(Admin $admin, Invoice $invoice, InvoiceStatus $newStatus): bool
    {
        $currentStatus = $invoice->status;
        return $currentStatus->canTransitionTo($newStatus);
    }
}
