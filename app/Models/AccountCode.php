<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class AccountCode extends Model
{
    use LogsActivity;

    protected $fillable = ['kelompok_belanja_id', 'code', 'name'];

    public function kelompokBelanja(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(KelompokBelanja::class);
    }

    public function accountPagus(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RbaAccountPagu::class);
    }
}
