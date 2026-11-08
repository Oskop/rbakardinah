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
        'target_unit_id',
        'target_sub_unit_id',
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

    public function targetUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'target_unit_id');
    }

    public function targetSubUnit(): BelongsTo
    {
        return $this->belongsTo(SubUnit::class, 'target_sub_unit_id');
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
     * Hanya User PIC (can_propose = true) dari Sub-Unit Kerja tujuan (atau target_operator_id) yang berhak mengelola.
     */
    public function canBeManagedBy(User $user): bool
    {
        if ($user->role !== 'Operator') {
            return false;
        }

        // Permohonan yang sudah diberi keputusan final tidak dapat dialihkan / dibalas lagi
        if (in_array($this->status, ['Dipenuhi', 'Dipenuhi Sebagian', 'Substitusi', 'Optimalisasi', 'Ditolak'])) {
            return false;
        }

        // Hanya operator dengan hak pengusulan (PIC / can_propose = true)
        if (!$user->isProposer()) {
            return false;
        }

        // Harus berada di Sub-Unit tujuan yang sama
        if ($this->target_sub_unit_id && $user->sub_unit_id === $this->target_sub_unit_id) {
            return true;
        }

        // Atau jika tidak ada sub-unit dan permohonan ke unit induk
        if (!$this->target_sub_unit_id && $this->target_unit_id && $user->unit_id === $this->target_unit_id) {
            return true;
        }

        // Fallback backward compatibility: jika target_operator_id sama persis
        return $this->target_operator_id === $user->id;
    }

    /**
     * Check if a given user can edit an already submitted reply on this submission.
     * Hanya PIC yang memberikan balasan atau PIC sub-unit target saat ini yang berhak mengedit.
     */
    public function canEditReply(User $user): bool
    {
        if ($user->role !== 'Operator' || !$user->isProposer()) {
            return false;
        }

        // Harus sudah ada balasan sebelumnya
        if (empty($this->replied_at)) {
            return false;
        }

        if ($this->replied_by === $user->id || $this->target_operator_id === $user->id) {
            return true;
        }

        if ($this->target_sub_unit_id && $user->sub_unit_id === $this->target_sub_unit_id) {
            return true;
        }

        if (!$this->target_sub_unit_id && $this->target_unit_id && $user->unit_id === $this->target_unit_id) {
            return true;
        }

        return false;
    }

    /**
     * Check if a given user can edit this submission.
     * Hanya Operator pemohon yang berhak mengedit,
     * dan hanya saat status masih 'Diajukan', belum ada balasan, dan belum pernah dialihkan.
     */
    public function canEditSubmission(User $user): bool
    {
        if ($user->role !== 'Operator') {
            return false;
        }

        if ($this->user_id !== $user->id) {
            return false;
        }

        return $this->status === 'Diajukan'
            && empty($this->replied_at)
            && !$this->histories()->where('action', 'Pengalihan')->exists();
    }
}

