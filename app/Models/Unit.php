<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;

class Unit extends Model
{
    use LogsActivity;

    protected $fillable = ['code', 'name', 'is_active'];

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

    /**
     * Get the users associated with the unit.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
