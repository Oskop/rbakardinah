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
        'kode_permendagri',
        'kode_level5',
        'nama_level5',
        'kode_level6',
        'nama_level6',
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

    public function scopeSearch($query, $keyword)
    {
        if (empty($keyword)) {
            return $query;
        }

        $cleanKeyword = str_replace([' ', '-', '_', '.', '/'], '', strtolower($keyword));

        return $query->where(function ($q) use ($keyword, $cleanKeyword) {
            $q->where('nama_barang', 'like', "%{$keyword}%")
              ->orWhereRaw("REPLACE(REPLACE(nama_barang, ' ', ''), '-', '') LIKE ?", ["%{$cleanKeyword}%"])
              ->orWhere('kode_barang', 'like', "%{$keyword}%")
              ->orWhere('kode_permendagri', 'like', "%{$keyword}%")
              ->orWhere('nama_level5', 'like', "%{$keyword}%")
              ->orWhere('nama_level6', 'like', "%{$keyword}%")
              ->orWhere('deskripsi', 'like', "%{$keyword}%");
        });
    }

    public function scopeLevel5($query, $kodeLevel5)
    {
        if (!empty($kodeLevel5)) {
            return $query->where('kode_level5', $kodeLevel5);
        }
        return $query;
    }

    public function scopeLevel6($query, $kodeLevel6)
    {
        if (!empty($kodeLevel6)) {
            return $query->where('kode_level6', $kodeLevel6);
        }
        return $query;
    }
}
