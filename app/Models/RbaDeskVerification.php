<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\LogsActivity;
use Carbon\Carbon;

class RbaDeskVerification extends Model
{
    use LogsActivity;

    protected $table = 'rba_desk_verifications';

    protected $fillable = [
        'rba_submission_id',
        'user_id',
        'hari',
        'tanggal_desk',
        'tanggal_desk_spelled',
        'ruang_desk',
        'sub_unit_name',
        'catatan',
        'is_usulan_sipakar',
        'kriteria_latar_belakang',
        'catatan_perbaikan_latar_belakang',
        'is_dokumen_rab_uploaded',
        'tim_asistensi',
        'anggota_sub_unit',
        'created_by',
    ];

    protected $casts = [
        'tanggal_desk' => 'date',
        'tim_asistensi' => 'array',
        'anggota_sub_unit' => 'array',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(RbaSubmission::class, 'rba_submission_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(RbaDeskVerificationDocument::class, 'rba_desk_verification_id')->orderBy('version_number', 'desc');
    }

    public function latestDocument(): HasOne
    {
        return $this->hasOne(RbaDeskVerificationDocument::class, 'rba_desk_verification_id')->latestOfMany('version_number');
    }

    /**
     * Helper to convert number to Indonesian words (terbilang).
     */
    public static function terbilang(int $number): string
    {
        $words = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];

        if ($number < 12) {
            return $words[$number];
        } elseif ($number < 20) {
            return self::terbilang($number - 10) . ' Belas';
        } elseif ($number < 100) {
            return self::terbilang((int) ($number / 10)) . ' Puluh ' . self::terbilang($number % 10);
        } elseif ($number < 200) {
            return 'Seratus ' . self::terbilang($number - 100);
        } elseif ($number < 1000) {
            return self::terbilang((int) ($number / 100)) . ' Ratus ' . self::terbilang($number % 100);
        } elseif ($number < 2000) {
            return 'Seribu ' . self::terbilang($number - 1000);
        } elseif ($number < 1000000) {
            return self::terbilang((int) ($number / 1000)) . ' Ribu ' . self::terbilang($number % 1000);
        }

        return (string) $number;
    }

    /**
     * Generate Indonesian spelling and day name for given date.
     * Example: 2026-09-05 -> [hari => 'Sabtu', spelled => 'tanggal Lima Bulan September Tahun Dua Ribu Dua Puluh Enam', formatted => '05-09-2026']
     */
    public static function parseDateComponents($date): array
    {
        $c = Carbon::parse($date);
        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $dayName = $days[$c->dayOfWeek];
        $daySpelled = trim(self::terbilang($c->day));
        $monthName = $months[$c->month];
        $yearSpelled = trim(self::terbilang($c->year));

        $spelled = "tanggal {$daySpelled} Bulan {$monthName} Tahun {$yearSpelled}";

        return [
            'hari' => $dayName,
            'tanggal_desk' => $c->format('Y-m-d'),
            'tanggal_desk_spelled' => $spelled,
            'formatted_digit' => $c->format('d-m-Y'),
        ];
    }
}
