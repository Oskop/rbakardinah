<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RbaSubmission extends Model
{
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
}
