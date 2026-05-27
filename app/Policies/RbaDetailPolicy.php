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

        // 2. Already submitted items (that are not rejected) are locked
        if ($rbaDetail->is_submitted && !$rbaDetail->is_rejected) {
            return Response::deny('Cannot update detail if it is already submitted and not rejected.');
        }

        // 3. Check if Pagu Global has been issued for this account and header
        // Exception: If status_global is NOT Draft, but Pagu is not yet set or 0, we still allow updates.
        if ($this->isPaguIssued($rbaDetail->submission->rba_header_id, $rbaDetail->account_code_id)) {
            return Response::deny('Cannot update nominal after Pagu has been issued (> 0) for this account.');
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

        // 3. Exception check for Pagu
        if ($this->isPaguIssued($rbaDetail->submission->rba_header_id, $rbaDetail->account_code_id)) {
             return Response::deny('Cannot delete items after Pagu has been issued (> 0) for this account.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, \App\Models\RbaSubmission $submission, int $accountCodeId): Response
    {
        // If Pagu has been issued (> 0), block creation regardless of global status
        if ($this->isPaguIssued($submission->rba_header_id, $accountCodeId)) {
            return Response::deny('Cannot add new items for an account that already has Pagu issued.');
        }

        // If global status is NOT Draft, we ONLY allow if Pagu is NOT issued (handled by logic above)
        // This is the core change: we don't return deny immediately if status_global is not Draft.

        return Response::allow();
    }

    /**
     * Determine whether the user can upload a new version of the attachment.
     */
    public function uploadVersion(User $user, RbaDetail $rbaDetail): Response
    {
        // 1. Check ownership
        if ($rbaDetail->created_by !== $user->id) {
            return Response::deny('You do not own this RBA detail.');
        }

        // 2. If pagu is not issued, they can upload version as long as it's not locked by submission status (or they can upload revisions to rejected items)
        if (!$this->isPaguIssued($rbaDetail->submission->rba_header_id, $rbaDetail->account_code_id)) {
            if ($rbaDetail->is_submitted && !$rbaDetail->is_rejected) {
                return Response::deny('Cannot update detail attachment if it is already submitted and not rejected.');
            }
            return Response::allow();
        }

        // 3. If pagu is issued, they can ONLY upload version if the nominal request exceeds the pagu
        if ($rbaDetail->isExceedingPagu()) {
            return Response::allow();
        }

        return Response::deny('Cannot upload revision for this account since it does not exceed the Pagu.');
    }

    /**
     * Determine whether the user can submit the detail.
     */
    public function submit(User $user, RbaDetail $rbaDetail): Response
    {
        // 1. Check ownership
        if ($rbaDetail->created_by !== $user->id) {
            return Response::deny('You do not own this RBA detail.');
        }

        // 2. Already submitted items (that are not rejected) are locked
        if ($rbaDetail->is_submitted && !$rbaDetail->is_rejected) {
            return Response::deny('Cannot submit detail if it is already submitted and not rejected.');
        }

        // 3. If pagu is issued
        if ($this->isPaguIssued($rbaDetail->submission->rba_header_id, $rbaDetail->account_code_id)) {
            // If it exceeds pagu, they can submit ONLY if they have uploaded the revision
            if ($rbaDetail->isExceedingPagu()) {
                if ($rbaDetail->hasUploadedRevision()) {
                    return Response::allow();
                }
                return Response::deny('Cannot submit. You must upload a new PDF matching the Pagu first.');
            }
            return Response::allow();
        }

        return Response::allow();
    }

    private function isPaguIssued(int $headerId, int $accountCodeId): bool
    {
        return RbaAccountPagu::where('rba_header_id', $headerId)
            ->where('account_code_id', $accountCodeId)
            ->where('nominal_pagu', '>', 0)
            ->exists();
    }
}
