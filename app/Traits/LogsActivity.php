<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    public static function bootLogsActivity()
    {
        static::created(function (Model $model) {
            static::recordActivity('created', $model, null, $model->getAttributes());
        });

        static::updated(function (Model $model) {
            $dirty = $model->getDirty();
            // Don't log if only timestamps changed
            unset($dirty['updated_at']);
            
            if (!empty($dirty)) {
                $old = array_intersect_key($model->getOriginal(), $dirty);
                static::recordActivity('updated', $model, $old, $dirty);
            }
        });

        static::deleted(function (Model $model) {
            static::recordActivity('deleted', $model, $model->getOriginal(), null);
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function (Model $model) {
                static::recordActivity('restored', $model, null, $model->getAttributes());
            });
        }
    }

    protected static function recordActivity(string $action, Model $model, ?array $old, ?array $new)
    {
        $sensitive = ['password', 'remember_token'];
        if ($old) {
            foreach ($sensitive as $s) unset($old[$s]);
        }
        if ($new) {
            foreach ($sensitive as $s) unset($new[$s]);
        }

        $user = Auth::user();
        $modelName = class_basename($model);
        $desc = static::generateActivityDescription($action, $modelName, $model, $user, $new);

        // Silent try-catch to ensure logging never breaks primary transactions
        try {
            ActivityLog::create([
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? 'System',
                'user_role' => $user?->role ?? 'System',
                'action' => $action,
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'description' => $desc,
                'old_values' => $old,
                'new_values' => $new,
                'ip_address' => Request::ip() ?? '127.0.0.1',
                'user_agent' => Request::userAgent(),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Failed to write activity log: ' . $e->getMessage());
        }
    }

    protected static function generateActivityDescription(string $action, string $modelName, Model $model, $user, ?array $new = null): string
    {
        $actor = $user ? "{$user->name} ({$user->role})" : 'Sistem';
        $key = $model->getKey();

        $actionVerb = match ($action) {
            'created' => 'menambahkan',
            'updated' => 'memperbarui',
            'deleted' => 'menghapus',
            'restored' => 'memulihkan',
            default => $action,
        };

        // Context-aware descriptions
        if ($modelName === 'RbaDetail') {
            $desc = $model->description ?? ($model->getOriginal('description') ?? "#{$key}");
            if ($action === 'updated' && isset($new['is_validated']) && $new['is_validated'] === true) {
                return "{$actor} memvalidasi Usulan RBA: \"{$desc}\"";
            }
            if ($action === 'updated' && isset($new['is_rejected']) && $new['is_rejected'] === true) {
                return "{$actor} menolak Usulan RBA: \"{$desc}\" (Alasan: " . ($new['rejection_reason'] ?? '-') . ")";
            }
            if ($action === 'updated' && isset($new['is_submitted']) && $new['is_submitted'] === true) {
                return "{$actor} mengajukan Usulan RBA: \"{$desc}\" ke Supervisor";
            }
            return "{$actor} {$actionVerb} Usulan RBA: \"{$desc}\"";
        }

        if ($modelName === 'RbaAccountPagu') {
            $nominal = number_format((float) ($model->nominal_pagu ?? 0), 0, ',', '.');
            if ($action === 'deleted') {
                return "{$actor} membatalkan penetapan Pagu Rekening #{$key}";
            }
            return "{$actor} {$actionVerb} penetapan Pagu Rekening sebesar Rp {$nominal}";
        }

        if ($modelName === 'User') {
            $userName = $model->name ?? ($model->getOriginal('name') ?? "#{$key}");
            return "{$actor} {$actionVerb} data Pengguna: \"{$userName}\"";
        }

        if ($modelName === 'Unit') {
            $unitName = $model->name ?? ($model->getOriginal('name') ?? "#{$key}");
            if ($action === 'updated' && isset($new['is_active'])) {
                $statusText = $new['is_active'] ? 'mengaktifkan' : 'menonaktifkan';
                return "{$actor} {$statusText} Unit Kerja: \"{$unitName}\"";
            }
            return "{$actor} {$actionVerb} data Unit: \"{$unitName}\"";
        }

        if ($modelName === 'KelompokBelanja') {
            $groupName = ($model->kode ?? '') . ' - ' . ($model->name ?? ($model->getOriginal('name') ?? "#{$key}"));
            if ($action === 'updated' && isset($new['is_active'])) {
                $statusText = $new['is_active'] ? 'mengaktifkan' : 'menonaktifkan';
                return "{$actor} {$statusText} Kelompok Belanja: \"{$groupName}\"";
            }
            return "{$actor} {$actionVerb} Kelompok Belanja: \"{$groupName}\"";
        }

        if ($modelName === 'AccountCode') {
            $codeName = ($model->code ?? '') . ' - ' . ($model->name ?? "#{$key}");
            return "{$actor} {$actionVerb} Nomor Rekening: \"{$codeName}\"";
        }

        if ($modelName === 'RbaAttachment') {
            $ver = $model->version_number ?? '1';
            return "{$actor} {$actionVerb} lampiran PDF dokumen (Versi {$ver})";
        }

        if ($modelName === 'RbaSubmissionDocumentVersion') {
            $ver = $model->version_number ?? '1';
            return "{$actor} mengunggah versi dokumen pendukung (Versi {$ver})";
        }

        return "{$actor} {$actionVerb} data {$modelName} #{$key}";
    }
}
