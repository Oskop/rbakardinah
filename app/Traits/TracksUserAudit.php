<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

trait TracksUserAudit
{
    public static function bootTracksUserAudit()
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                if (Schema::hasColumn($model->getTable(), 'created_by') && empty($model->created_by)) {
                    $model->created_by = Auth::id();
                }
                if (Schema::hasColumn($model->getTable(), 'updated_by') && empty($model->updated_by)) {
                    $model->updated_by = Auth::id();
                }
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                if (Schema::hasColumn($model->getTable(), 'updated_by')) {
                    $model->updated_by = Auth::id();
                }
            }
        });

        static::deleting(function ($model) {
            if (Auth::check()) {
                if (Schema::hasColumn($model->getTable(), 'deleted_by')) {
                    $model->deleted_by = Auth::id();
                    $model->saveQuietly();
                }
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    public function deleter()
    {
        return $this->belongsTo(\App\Models\User::class, 'deleted_by');
    }
}
