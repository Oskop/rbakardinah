<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RbaHeader extends Model
{
    protected $fillable = ['period_id', 'admin_id', 'year', 'status_global'];

    public function period(): BelongsTo
    {
        return $this->belongsTo(RbaPeriod::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class , 'admin_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(RbaSubmission::class);
    }

    public function accountPagus(): HasMany
    {
        return $this->hasMany(RbaAccountPagu::class);
    }
}
