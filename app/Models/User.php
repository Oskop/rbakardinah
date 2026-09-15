<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\LogsActivity;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, LogsActivity;

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'simrs_sub',
        'nip',
        'password',
        'role',
        'auth_provider',
        'simrs_metadata',
        'unit_id',
        'sub_unit_id',
        'can_propose',
        'menu_permissions',
        'jabatan',
        'is_active',
    ];

    /**
     * Daftar menu delegasi yang dapat diberikan oleh Administrator kepada user non-admin.
     */
    public const DELEGATABLE_MENUS = [
        'master_barangs' => [
            'label' => 'Master Barang BMD',
            'icon' => '📦',
            'route' => 'admin.master-barangs.index',
            'description' => 'Pengelolaan katalog Master Barang Milik Daerah (Permendagri No. 108/2016)',
        ],
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'can_propose' => 'boolean',
            'menu_permissions' => 'array',
            'simrs_metadata' => 'array',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saving(function (User $user) {
            // Auto-sync unit_id from sub_unit if sub_unit_id is provided
            if ($user->sub_unit_id && !$user->unit_id) {
                $subUnit = SubUnit::find($user->sub_unit_id);
                if ($subUnit) {
                    $user->unit_id = $subUnit->unit_id;
                }
            }
        });
    }

    /**
     * Get the parent unit (Eselon III) that the user belongs to.
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the operational sub-unit (Eselon IV / Non-Eselon) that the user belongs to.
     */
    public function subUnit()
    {
        return $this->belongsTo(SubUnit::class);
    }

    /**
     * Helper accessor to get the resolved sub-unit or unit name.
     */
    public function getSubUnitNameAttribute(): string
    {
        if ($this->subUnit) {
            return $this->subUnit->name;
        }

        return $this->unit ? $this->unit->name : '-';
    }

    /**
     * Helper accessor to get full organizational hierarchy label.
     */
    public function getOrganizationLabelAttribute(): string
    {
        if ($this->subUnit && $this->unit) {
            return "{$this->subUnit->name} ({$this->unit->name})";
        }

        if ($this->subUnit) {
            return $this->subUnit->name;
        }

        return $this->unit ? $this->unit->name : 'Tanpa Unit';
    }

    /**
     * Get announcements specifically targeted to this user.
     */
    public function targetedAnnouncements()
    {
        return $this->belongsToMany(Announcement::class, 'announcement_user')->withTimestamps();
    }

    /**
     * Check if user is an Operator with RBA proposing permission.
     */
    public function isProposer(): bool
    {
        return $this->role === 'Operator' && (bool) ($this->can_propose ?? true);
    }

    /**
     * Periksa apakah pengguna memiliki hak akses ke menu tertentu (termasuk menu delegasi).
     */
    public function hasMenuPermission(string $menuKey): bool
    {
        if ($this->role === 'Administrator') {
            return true;
        }

        $permissions = $this->menu_permissions ?? [];
        return in_array($menuKey, $permissions, true);
    }

    /**
     * Ambil daftar konfigurasi menu yang didelegasikan untuk pengguna ini.
     */
    public function getDelegatedMenus(): array
    {
        if ($this->role === 'Administrator') {
            return self::DELEGATABLE_MENUS;
        }

        $permissions = $this->menu_permissions ?? [];
        return array_filter(self::DELEGATABLE_MENUS, function ($key) use ($permissions) {
            return in_array($key, $permissions, true);
        }, ARRAY_FILTER_USE_KEY);
    }
}

