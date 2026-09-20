<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class RbaDeskVerificationDocument extends Model
{
    use LogsActivity;

    protected $table = 'rba_desk_verification_documents';

    protected $fillable = [
        'rba_desk_verification_id',
        'version_number',
        'file_path',
        'original_filename',
        'notes',
        'uploaded_by',
    ];

    public function deskVerification(): BelongsTo
    {
        return $this->belongsTo(RbaDeskVerification::class, 'rba_desk_verification_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
