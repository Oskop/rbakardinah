<?php

namespace App\Policies;

use App\Models\RbaDetail;
use App\Models\User;
use App\Models\RbaAccountPagu;
use Illuminate\Auth\Access\Response;

class RbaDetailPolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RbaDetail $rbaDetail): Response
    {
        // 1. Check ownership
        if ($rbaDetail->created_by !== $user->id) {
            return Response::deny('You do not own this RBA detail.');
        }

        // 2. Check status
        if ($rbaDetail->submission->header->status_global !== 'Draft' || ($rbaDetail->is_submitted && !$rbaDetail->is_rejected)) {
            return Response::deny('Cannot update detail if it is already submitted (and not rejected) or the global status is no longer Draft.');
        }

        // 3. Check if Pagu Global has been issued for this account and header
        $paguExists = RbaAccountPagu::where('rba_header_id', $rbaDetail->submission->rba_header_id)
            ->where('account_code_id', $rbaDetail->account_code_id)
            ->exists();

        if ($paguExists) {
            return Response::deny('Cannot update nominal after Pagu has been issued for this account.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RbaDetail $rbaDetail): Response
    {
        // 1. Check ownership
        if ($rbaDetail->created_by !== $user->id) {
            return Response::deny('You do not own this RBA detail.');
        }

        // 2. Check validation or submission status
        if ($rbaDetail->is_validated) {
            return Response::deny('Cannot delete validated items.');
        }

        if ($rbaDetail->is_submitted && !$rbaDetail->is_rejected) {
            return Response::deny('Cannot delete items that are pending supervisor review.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, \App\Models\RbaSubmission $submission, int $accountCodeId): Response
    {
        // 1. Check if global header is in draft
        if ($submission->header->status_global !== 'Draft') {
            return Response::deny('Cannot add details to a submission when the global status is not Draft.');
        }

        // 2. Check if Pagu Global has been issued
        $paguExists = RbaAccountPagu::where('rba_header_id', $submission->rba_header_id)
            ->where('account_code_id', $accountCodeId)
            ->exists();

        if ($paguExists) {
            return Response::deny('Cannot add new items for an account that already has Pagu issued.');
        }

        return Response::allow();
    }
}
