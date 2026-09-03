<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class KelompokBelanja extends Model
{
    use LogsActivity;

    protected $fillable = ['kode', 'name', 'is_active'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function accountCodes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AccountCode::class);
    }
}
