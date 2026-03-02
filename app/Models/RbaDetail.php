<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RbaDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'rba_submission_id',
        'account_code_id',
        'description',
        'nominal_request',
        'is_submitted',
        'is_validated',
        'validated_at',
        'validated_by',
        'is_rejected',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'created_by'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_submitted' => 'boolean',
            'is_validated' => 'boolean',
            'validated_at' => 'datetime',
            'is_rejected' => 'boolean',
            'rejected_at' => 'datetime',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(RbaSubmission::class, 'rba_submission_id');
    }

    public function accountCode(): BelongsTo
    {
        return $this->belongsTo(AccountCode::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(RbaAttachment::class);
    }

    public function latestAttachment()
    {
        return $this->attachments()->orderByDesc('version_number')->first();
    }
}
