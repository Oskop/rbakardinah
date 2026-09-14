<?php

namespace App\Models;

use App\Traits\LogsActivity;
use App\Traits\TracksUserAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RkbmdSubmission extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, TracksUserAudit;

    protected $table = 'rkbmd_submissions';

    protected $fillable = [
        'nomor_permohonan',
        'year',
        'user_id',
        'unit_id',
        'sub_unit_id',
        'target_operator_id',
        'original_operator_id',
        'title',
        'notes',
        'attachment_path',
        'status',
        'reply_notes',
        'replied_at',
        'replied_by',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'replied_at' => 'datetime',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function subUnit(): BelongsTo
    {
        return $this->belongsTo(SubUnit::class, 'sub_unit_id');
    }

    public function targetOperator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_operator_id');
    }

    public function originalOperator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'original_operator_id');
    }

    public function repliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RkbmdItem::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(RkbmdHistory::class)->orderBy('created_at', 'asc');
    }

    /**
     * Check if a given user can manage (reply/forward) this submission.
     */
    public function canBeManagedBy(User $user): bool
    {
        if ($user->role !== 'Operator' && $user->role !== 'Administrator') {
            return false;
        }

        // Permohonan yang sudah diberi keputusan final tidak dapat dialihkan / dibalas lagi
        if (in_array($this->status, ['Dipenuhi', 'Dipenuhi Sebagian', 'Substitusi', 'Optimalisasi', 'Ditolak'])) {
            return false;
        }

        return $this->target_operator_id === $user->id;
    }
}
