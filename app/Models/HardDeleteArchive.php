<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class HardDeleteArchive extends Model
{
    use LogsActivity;

    protected $fillable = [
        'model_type',
        'model_id',
        'label',
        'reason',
        'root_data',
        'related_data',
        'metadata',
        'deleted_by',
        'deleted_at',
        'restored_at',
        'restored_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'root_data' => 'array',
            'related_data' => 'array',
            'metadata' => 'array',
            'deleted_at' => 'datetime',
            'restored_at' => 'datetime',
        ];
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function restoredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'restored_by');
    }

    public function isRestored(): bool
    {
        return !is_null($this->restored_at);
    }

    public function scopeUnrestored($query)
    {
        return $query->whereNull('restored_at');
    }

    public function scopeRestored($query)
    {
        return $query->whereNotNull('restored_at');
    }
}

