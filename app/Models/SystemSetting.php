<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'updated_by',
    ];

    /**
     * Relasi ke user yang terakhir kali memperbarui setting.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Mengambil nilai setting dengan tipe data yang sesuai.
     */
    public function getCastedValueAttribute()
    {
        return self::castValue($this->value, $this->type);
    }

    /**
     * Helper casting nilai berdasarkan kolom tipe data.
     */
    public static function castValue($value, string $type)
    {
        if (is_null($value)) {
            return null;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer', 'int' => (int) $value,
            'float', 'double' => (float) $value,
            'json', 'array' => json_decode($value, true),
            default => (string) $value,
        };
    }

    /**
     * Mendapatkan nilai setting berdasarkan key dengan fallback default.
     */
    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return $setting->casted_value;
    }

    /**
     * Menyimpan atau memperbarui nilai setting.
     */
    public static function set(string $key, $value, ?int $userId = null): self
    {
        $setting = self::firstOrNew(['key' => $key]);

        $stringValue = is_bool($value) ? ($value ? '1' : '0') : (is_array($value) ? json_encode($value) : (string) $value);

        $setting->value = $stringValue;
        if ($userId) {
            $setting->updated_by = $userId;
        }
        $setting->save();

        return $setting;
    }

    /**
     * Helper spesifik: memeriksa apakah tombol dan aksi Hapus RBA diizinkan.
     * Default: false (aman/terkunci).
     */
    public static function isRbaDeletionAllowed(): bool
    {
        $setting = self::where('key', 'allow_rba_deletion')->first();

        if ($setting) {
            return (bool) $setting->casted_value;
        }

        // Fallback ke environment variable jika belum ada di database
        return (bool) env('ENABLE_RBA_DELETION', false);
    }
}

