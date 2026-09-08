<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class PerformanceIndicatorTargetHistory extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'target_id',
        'performance_indicator_id',
        'year',
        'version_number',
        'old_value',
        'new_value',
        'change_note',
        'user_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'version_number' => 'integer',
        ];
    }

    /**
     * Relasi ke target tahunan.
     */
    public function target(): BelongsTo
    {
        return $this->belongsTo(PerformanceIndicatorTarget::class, 'target_id');
    }

    /**
     * Relasi ke indikator kinerja induk.
     */
    public function indicator(): BelongsTo
    {
        return $this->belongsTo(PerformanceIndicator::class, 'performance_indicator_id');
    }

    /**
     * Relasi ke user pengubah (Admin).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
