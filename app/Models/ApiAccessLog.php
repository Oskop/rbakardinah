<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiAccessLog extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'api_client_id',
        'client_name',
        'endpoint',
        'method',
        'status_code',
        'query_params',
        'response_time_ms',
        'ip_address',
        'user_agent',
        'error_message',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'query_params' => 'array',
            'status_code' => 'integer',
            'response_time_ms' => 'float',
            'created_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class , 'api_client_id');
    }

    /**
     * Check if the request was successful (2xx).
     */
    public function isSuccess(): bool
    {
        return $this->status_code >= 200 && $this->status_code < 300;
    }

    /**
     * Get CSS badge class for HTTP status code.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        if ($this->status_code >= 200 && $this->status_code < 300) {
            return 'bg-emerald-100 text-emerald-800 border-emerald-300';
        }

        if ($this->status_code === 429) {
            return 'bg-amber-100 text-amber-800 border-amber-300';
        }

        if ($this->status_code >= 400 && $this->status_code < 500) {
            return 'bg-rose-100 text-rose-800 border-rose-300';
        }

        return 'bg-purple-100 text-purple-800 border-purple-300';
    }
}
