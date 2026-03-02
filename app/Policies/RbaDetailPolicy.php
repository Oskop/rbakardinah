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
        if ($rbaDetail->submission->status_submission !== 'Draft' && !$rbaDetail->is_rejected) {
            return Response::deny('Cannot update detail if it is not in Draft status and not Rejected.');
        }

        // 2. Check if Pagu Global has been issued for this account and header
        $paguExists = RbaAccountPagu::where('rba_header_id', $rbaDetail->submission->rba_header_id)
            ->where('account_code_id', $rbaDetail->account_code_id)
            ->exists();

        if ($paguExists) {
            return Response::deny('Cannot update nominal after Pagu has been issued for this account.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, \App\Models\RbaSubmission $submission, int $accountCodeId): Response
    {
        // 1. Check if submission is in draft
        if ($submission->status_submission !== 'Draft') {
            return Response::deny('Cannot add details to a submission that is not in Draft status.');
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
