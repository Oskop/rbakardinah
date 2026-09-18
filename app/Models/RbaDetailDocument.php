<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\LogsActivity;

class RbaDetailDocument extends Model
{
    use LogsActivity;

    protected $fillable = [
        'rba_submission_id',
        'document_name',
        'created_by',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(RbaSubmission::class, 'rba_submission_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(RbaAttachment::class, 'rba_detail_document_id')->orderByDesc('version_number');
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(RbaAttachment::class, 'rba_detail_document_id')->latestOfMany('version_number');
    }
}
