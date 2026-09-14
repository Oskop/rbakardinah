<?php

namespace App\Models;

use App\Traits\LogsActivity;
use App\Traits\TracksUserAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RkbmdHistory extends Model
{
    use HasFactory, LogsActivity, TracksUserAudit;

    protected $table = 'rkbmd_histories';

    protected $fillable = [
        'rkbmd_submission_id',
        'user_id',
        'action',
        'from_operator_id',
        'to_operator_id',
        'status_before',
        'status_after',
        'notes',
        'created_by',
        'updated_by',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(RkbmdSubmission::class, 'rkbmd_submission_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fromOperator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_operator_id');
    }

    public function toOperator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_operator_id');
    }
}
