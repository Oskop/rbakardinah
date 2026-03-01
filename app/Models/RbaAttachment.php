<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RbaAttachment extends Model
{
    protected $fillable = [
        'rba_detail_id',
        'file_path',
        'version_number',
        'uploaded_by'
    ];

    public function detail(): BelongsTo
    {
        return $this->belongsTo(RbaDetail::class, 'rba_detail_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
