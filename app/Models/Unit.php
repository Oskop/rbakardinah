<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $fillable = ['code', 'name'];

    /**
     * Get the users associated with the unit.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
