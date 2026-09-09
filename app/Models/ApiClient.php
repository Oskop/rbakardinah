<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'key_prefix',
        'api_key_hash',
        'is_active',
        'expires_at',
        'last_used_at',
        'last_used_ip',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * Generate a new API Client with a secure token.
     * Returns an array containing the model instance and the unhashed plain token.
     *
     * @return array{client: ApiClient, plain_token: string}
     */
    public static function createWithToken(string $name, ?int $expiresInDays = null): array
    {
        // Format: rba_live_ + 48 random characters
        $randomPart = Str::random(48);
        $plainToken = 'rba_live_' . $randomPart;
        $prefix = substr($plainToken, 0, 16);
        $hash = hash('sha256', $plainToken);

        $client = self::create([
            'name' => $name,
            'key_prefix' => $prefix,
            'api_key_hash' => $hash,
            'is_active' => true,
            'expires_at' => $expiresInDays ? now()->addDays($expiresInDays) : null,
        ]);

        return [
            'client' => $client,
            'plain_token' => $plainToken,
        ];
    }

    /**
     * Find and verify an active client by its plain token string.
     */
    public static function findByPlainToken(string $plainToken): ?self
    {
        $hash = hash('sha256', trim($plainToken));

        return self::where('api_key_hash', $hash)->first();
    }

    /**
     * Check if the API key is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Check if the API key is valid for use.
     */
    public function isValid(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    /**
     * Record usage timestamp and IP address.
     */
    public function recordUsage(?string $ipAddress = null): void
    {
        $this->update([
            'last_used_at' => now(),
            'last_used_ip' => $ipAddress,
        ]);
    }
}
