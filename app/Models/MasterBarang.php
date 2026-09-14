<?php

namespace App\Models;

use App\Traits\LogsActivity;
use App\Traits\TracksUserAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterBarang extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, TracksUserAudit;

    protected $table = 'master_barangs';

    protected $fillable = [
        'kode_barang',
        'nama_barang',
        'satuan',
        'deskripsi',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(RkbmdItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
