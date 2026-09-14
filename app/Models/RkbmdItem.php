<?php

namespace App\Models;

use App\Traits\LogsActivity;
use App\Traits\TracksUserAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RkbmdItem extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, TracksUserAudit;

    protected $table = 'rkbmd_items';

    protected $fillable = [
        'rkbmd_submission_id',
        'master_barang_id',
        'volume',
        'satuan',
        'spesifikasi',
        'volume_disetujui',
        'status_item',
        'catatan_operator',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'volume' => 'float',
            'volume_disetujui' => 'float',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(RkbmdSubmission::class, 'rkbmd_submission_id');
    }

    public function masterBarang(): BelongsTo
    {
        return $this->belongsTo(MasterBarang::class, 'master_barang_id');
    }
}
