<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class PerformanceIndicatorTarget extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'performance_indicator_id',
        'year',
        'target_value',
        'current_version',
        'created_by',
        'updated_by',
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
            'current_version' => 'integer',
        ];
    }

    /**
     * Relasi ke indikator induk.
     */
    public function indicator(): BelongsTo
    {
        return $this->belongsTo(PerformanceIndicator::class, 'performance_indicator_id');
    }

    /**
     * Relasi ke riwayat versi pengeditan target.
     */
    public function histories(): HasMany
    {
        return $this->hasMany(PerformanceIndicatorTargetHistory::class, 'target_id')->orderBy('version_number', 'desc');
    }

    /**
     * Relasi ke user pembuat target awal.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke user pengubah target terakhir.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
