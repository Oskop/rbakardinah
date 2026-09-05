<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;

class RbaSubmission extends Model
{
    use LogsActivity;

    protected $fillable = ['rba_header_id', 'unit_id', 'status_submission', 'supervisor_note', 'background'];

    public function header(): BelongsTo
    {
        return $this->belongsTo(RbaHeader::class , 'rba_header_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(RbaDetail::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(RbaSubmissionDocument::class);
    }

    public function operatorBackgrounds(): HasMany
    {
        return $this->hasMany(RbaSubmissionOperatorBackground::class);
    }

    /**
     * Synchronize and automatically compute the macro status of this submission
     * based on its submitted details validation states.
     */
    public function syncValidationStatus(): string
    {
        $submittedDetails = $this->details()->where('is_submitted', true)->get();

        if ($submittedDetails->isEmpty()) {
            $newStatus = 'Draft';
        } else {
            $totalSubmitted = $submittedDetails->count();
            $totalValidated = $submittedDetails->where('is_validated', true)->count();
            $hasRejection = $submittedDetails->where('is_rejected', true)->isNotEmpty();

            if ($totalValidated === $totalSubmitted && !$hasRejection) {
                $newStatus = 'Validated';
            } else {
                $newStatus = 'Pending Supervisor';
            }
        }

        if ($this->status_submission !== $newStatus) {
            $this->update(['status_submission' => $newStatus]);
        }

        return $newStatus;
    }
}
