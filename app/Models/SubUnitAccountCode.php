<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class SubUnitAccountCode extends Model
{
    use LogsActivity;

    protected $fillable = [
        'sub_unit_id',
        'account_code_id',
        'fiscal_year',
        'keterangan_khusus',
    ];

    /**
     * Get the sub-unit that owns the mapping.
     */
    public function subUnit(): BelongsTo
    {
        return $this->belongsTo(SubUnit::class);
    }

    /**
     * Get the account code that belongs to this mapping.
     */
    public function accountCode(): BelongsTo
    {
        return $this->belongsTo(AccountCode::class);
    }
}
