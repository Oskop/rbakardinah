<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RbaAccountPagu extends Model
{
    protected $fillable = ['rba_header_id', 'account_code_id', 'nominal_pagu'];

    public function header(): BelongsTo
    {
        return $this->belongsTo(RbaHeader::class , 'rba_header_id');
    }

    public function accountCode(): BelongsTo
    {
        return $this->belongsTo(AccountCode::class);
    }
}
