<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class RbaSubmissionOperatorBackground extends Model
{
    use LogsActivity;

    protected $table = 'rba_submission_operator_backgrounds';

    protected $fillable = [
        'rba_submission_id',
        'user_id',
        'background',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(RbaSubmission::class, 'rba_submission_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
