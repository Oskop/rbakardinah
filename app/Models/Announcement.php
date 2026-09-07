<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Announcement extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'title',
        'content',
        'type',
        'target_type',
        'start_at',
        'end_at',
        'is_active',
        'reshown_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'is_active' => 'boolean',
            'reshown_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke pembuat pengumuman (Admin).
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke daftar user sasaran (jika target_type === specific_users).
     */
    public function targetUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_user')->withTimestamps();
    }

    /**
     * Scope pengumuman aktif dan relevan untuk user tertentu saat ini.
     */
    public function scopeActiveForUser($query, User $user)
    {
        $now = now();

        return $query->where('is_active', true)
            ->where('start_at', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
            })
            ->where(function ($q) use ($user) {
                $q->where('target_type', 'all')
                    ->orWhere(function ($sub) use ($user) {
                        $sub->where('target_type', 'all_supervisors')
                            ->whereRaw('? = ?', [$user->role, 'Supervisor']);
                    })
                    ->orWhere(function ($sub) use ($user) {
                        $sub->where('target_type', 'all_operators')
                            ->whereRaw('? = ?', [$user->role, 'Operator']);
                    })
                    ->orWhere(function ($sub) use ($user) {
                        $sub->where('target_type', 'specific_users')
                            ->whereHas('targetUsers', function ($u) use ($user) {
                                $u->where('users.id', $user->id);
                            });
                    });
            });
    }

    /**
     * Memeriksa apakah pengumuman sedang tayang saat ini.
     */
    public function isCurrentlyActive(): bool
    {
        $now = now();
        return $this->is_active && $this->start_at->lte($now) && (!$this->end_at || $this->end_at->gte($now));
    }

    /**
     * Status komputasi penayangan pengumuman.
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        $now = now();

        if ($this->end_at && $this->end_at->lt($now)) {
            return 'expired';
        }

        if ($this->start_at->gt($now)) {
            return 'scheduled';
        }

        return 'running';
    }

    /**
     * Label teks dan badge class untuk status penayangan.
     */
    public function getStatusInfoAttribute(): array
    {
        return match ($this->status) {
            'inactive' => [
                'label' => 'Disembunyikan',
                'badge' => 'bg-slate-100 text-slate-700 border-slate-300',
                'class' => 'bg-slate-100 text-slate-700 border-slate-300',
                'dot' => 'bg-slate-400',
            ],
            'expired' => [
                'label' => 'Telah Berakhir',
                'badge' => 'bg-gray-100 text-gray-600 border-gray-200',
                'class' => 'bg-gray-100 text-gray-600 border-gray-200',
                'dot' => 'bg-gray-400',
            ],
            'scheduled' => [
                'label' => 'Terjadwal',
                'badge' => 'bg-sky-50 text-sky-800 border-sky-200',
                'class' => 'bg-sky-50 text-sky-800 border-sky-200',
                'dot' => 'bg-sky-500',
            ],
            default => [
                'label' => 'Sedang Tayang',
                'badge' => 'bg-emerald-50 text-emerald-800 border-emerald-300 shadow-2xs',
                'class' => 'bg-emerald-50 text-emerald-800 border-emerald-300 shadow-2xs',
                'dot' => 'bg-emerald-500 animate-pulse',
            ],
        };
    }

    /**
     * Kunci unik penyimpanan status tutup (dismissal) berbasis timestamp penegasan/pembaruan.
     */
    public function getDismissKeyAttribute(): string
    {
        $timestamp = $this->reshown_at ? $this->reshown_at->timestamp : ($this->updated_at ? $this->updated_at->timestamp : 0);
        return "announcement_dismissed_{$this->id}_{$timestamp}";
    }

    /**
     * Munculkan ulang / pertegas pengumuman kepada seluruh pengguna sasaran.
     */
    public function reshow(): void
    {
        $this->update([
            'is_active' => true,
            'reshown_at' => now(),
        ]);
    }
}
