<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentationArticle extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'documentation_version_id',
        'category',
        'title',
        'slug',
        'icon',
        'order',
        'content',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(DocumentationVersion::class, 'documentation_version_id');
    }
}
