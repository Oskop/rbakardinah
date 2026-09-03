<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class RbaPeriod extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'is_active'];

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

    public function headers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RbaHeader::class, 'period_id');
    }
}
