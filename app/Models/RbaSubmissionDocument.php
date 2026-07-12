<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RbaSubmissionDocument extends Model
{
    protected $fillable = [
        'rba_submission_id',
        'type'
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(RbaSubmission::class, 'rba_submission_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(RbaSubmissionDocumentVersion::class, 'rba_submission_document_id')->orderBy('version_number', 'desc');
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(RbaSubmissionDocumentVersion::class, 'rba_submission_document_id')->latestOfMany('version_number');
    }
}
