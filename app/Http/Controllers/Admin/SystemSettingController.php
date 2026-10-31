<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    /**
     * Tampilkan halaman pengelolaan pengaturan sistem.
     */
    public function index()
    {
        $settings = SystemSetting::with('updatedBy')->get()->keyBy('key');
        $allowRbaDeletion = SystemSetting::isRbaDeletionAllowed();

        $recentLogs = ActivityLog::where('model_type', SystemSetting::class)
            ->latest()
            ->take(5)
            ->get();

        return view('admin.settings.index', compact('settings', 'allowRbaDeletion', 'recentLogs'));
    }

    /**
     * Perbarui pengaturan sistem.
     */
    public function update(Request $request)
    {
        $request->validate([
            'allow_rba_deletion' => 'required|boolean',
        ]);

        $oldValue = SystemSetting::isRbaDeletionAllowed();
        $newValue = $request->boolean('allow_rba_deletion');

        $setting = SystemSetting::set('allow_rba_deletion', $newValue, auth()->id());

        // Catat ke Activity Log untuk kepatuhan audit BPK
        ActivityLog::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'user_role' => auth()->user()->role,
            'action' => 'updated',
            'model_type' => SystemSetting::class,
            'model_id' => $setting->id,
            'description' => $newValue
                ? 'Mengaktifkan izin dan tombol Hapus RBA Header (Mode Pembersihan Aktif)'
                : 'Menonaktifkan dan mengunci izin/tombol Hapus RBA Header (Mode Aman Terkunci)',
            'old_values' => ['allow_rba_deletion' => $oldValue],
            'new_values' => ['allow_rba_deletion' => $newValue],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $statusMsg = $newValue
            ? 'Fitur Hapus RBA diaktifkan! Tombol Hapus RBA kini muncul di halaman daftar RBA Header.'
            : 'Fitur Hapus RBA dikunci! Tombol Hapus RBA kini disembunyikan dan dilindungi.';

        return redirect()->route('admin.settings.index')->with('success', $statusMsg);
    }
}

