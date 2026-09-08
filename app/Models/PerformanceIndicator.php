<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class PerformanceIndicator extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'category',
        'unit',
        'description',
        'order',
        'is_active',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    /**
     * Relasi ke target tahunan indikator kinerja.
     */
    public function targets(): HasMany
    {
        return $this->hasMany(PerformanceIndicatorTarget::class, 'performance_indicator_id');
    }

    /**
     * Relasi ke user pembuat data.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Helper untuk mengambil target pada tahun tertentu.
     */
    public function getTargetByYear(int $year): ?PerformanceIndicatorTarget
    {
        if ($this->relationLoaded('targets')) {
            return $this->targets->firstWhere('year', $year);
        }

        return $this->targets()->where('year', $year)->first();
    }
}
