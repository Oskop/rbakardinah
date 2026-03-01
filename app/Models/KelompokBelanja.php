<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KelompokBelanja extends Model
{
    protected $fillable = ['name'];

    public function accountCodes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AccountCode::class);
    }
}
