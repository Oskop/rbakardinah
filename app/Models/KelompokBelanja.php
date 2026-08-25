<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class KelompokBelanja extends Model
{
    use LogsActivity;

    protected $fillable = ['kode', 'name'];

    public function accountCodes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AccountCode::class);
    }
}
