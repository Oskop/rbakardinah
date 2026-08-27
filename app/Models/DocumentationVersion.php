<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentationVersion extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'type',
        'version',
        'title',
        'file_path',
        'file_size',
        'release_notes',
        'released_at',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'released_at' => 'date',
        'is_active' => 'boolean',
        'file_size' => 'integer',
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(DocumentationArticle::class)->orderBy('order')->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeHtml($query)
    {
        return $query->where('type', 'html');
    }

    public function scopePdf($query)
    {
        return $query->where('type', 'pdf');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return '-';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
