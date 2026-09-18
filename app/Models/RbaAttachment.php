<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class RbaAttachment extends Model
{
    use LogsActivity;

    protected $fillable = [
        'rba_detail_document_id',
        'rba_detail_id',
        'file_path',
        'original_filename',
        'version_number',
        'uploaded_by'
    ];

    protected static function booted(): void
    {
        static::created(function (RbaAttachment $attachment) {
            if ($attachment->rba_detail_id) {
                \Illuminate\Support\Facades\DB::table('rba_detail_attachments')->insertOrIgnore([
                    'rba_detail_id' => $attachment->rba_detail_id,
                    'rba_attachment_id' => $attachment->id,
                    'created_at' => $attachment->created_at ?? now(),
                    'updated_at' => $attachment->updated_at ?? now(),
                ]);
            }
        });
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(RbaDetailDocument::class, 'rba_detail_document_id');
    }

    public function details()
    {
        return $this->belongsToMany(RbaDetail::class, 'rba_detail_attachments')->withTimestamps();
    }

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
