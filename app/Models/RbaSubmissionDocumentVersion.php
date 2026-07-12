<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RbaSubmissionDocumentVersion extends Model
{
    protected $fillable = [
        'rba_submission_document_id',
        'file_path',
        'version_number',
        'uploaded_by'
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(RbaSubmissionDocument::class, 'rba_submission_document_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
