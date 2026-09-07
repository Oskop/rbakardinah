<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    /**
     * Tampilkan daftar pengumuman untuk Administrator.
     */
    public function index(Request $request)
    {
        $statusFilter = $request->query('status', 'all');
        $search = $request->query('search');

        $now = now();

        $query = Announcement::with(['creator'])->withCount('targetUsers')->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Hitung statistik status untuk tabs
        $stats = [
            'all' => Announcement::count(),
            'running' => Announcement::where('is_active', true)
                ->where('start_at', '<=', $now)
                ->where(function ($q) use ($now) {
                    $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
                })->count(),
            'scheduled' => Announcement::where('is_active', true)
                ->where('start_at', '>', $now)->count(),
            'expired' => Announcement::whereNotNull('end_at')
                ->where('end_at', '<', $now)->count(),
            'inactive' => Announcement::where('is_active', false)->count(),
        ];

        // Filter berdasarkan tab status
        if ($statusFilter === 'running') {
            $query->where('is_active', true)
                ->where('start_at', '<=', $now)
                ->where(function ($q) use ($now) {
                    $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
                });
        } elseif ($statusFilter === 'scheduled') {
            $query->where('is_active', true)
                ->where('start_at', '>', $now);
        } elseif ($statusFilter === 'expired') {
            $query->whereNotNull('end_at')
                ->where('end_at', '<', $now);
        } elseif ($statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        $announcements = $query->paginate(15)->withQueryString();

        return view('admin.announcements.index', compact('announcements', 'statusFilter', 'search', 'stats'));
    }

    /**
     * Tampilkan form pembuatan pengumuman baru.
     */
    public function create()
    {
        $units = Unit::where('is_active', true)
            ->with(['users' => function ($q) {
                $q->where('is_active', true)->whereIn('role', ['Supervisor', 'Operator'])->orderBy('role')->orderBy('name');
            }])
            ->orderBy('name')
            ->get();

        $allSupervisorsCount = User::where('is_active', true)->where('role', 'Supervisor')->count();
        $allOperatorsCount = User::where('is_active', true)->where('role', 'Operator')->count();

        return view('admin.announcements.create', compact('units', 'allSupervisorsCount', 'allOperatorsCount'));
    }

    /**
     * Simpan pengumuman baru ke database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:info,warning,danger,success',
            'target_type' => 'required|in:all,all_supervisors,all_operators,specific_users',
            'start_at' => 'required|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'is_active' => 'nullable|boolean',
            'target_user_ids' => 'required_if:target_type,specific_users|array',
            'target_user_ids.*' => 'exists:users,id',
        ], [
            'title.required' => 'Judul pengumuman wajib diisi.',
            'content.required' => 'Isi pesan pengumuman wajib diisi.',
            'start_at.required' => 'Waktu mulai penayangan wajib ditentukan.',
            'end_at.after_or_equal' => 'Waktu selesai harus sama atau setelah waktu mulai.',
            'target_user_ids.required_if' => 'Harap pilih minimal satu pengguna jika sasaran adalah pengguna tertentu.',
        ]);

        $announcement = Announcement::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'type' => $validated['type'],
            'target_type' => $validated['target_type'],
            'start_at' => $validated['start_at'],
            'end_at' => $validated['end_at'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
        ]);

        if ($validated['target_type'] === 'specific_users') {
            $announcement->targetUsers()->sync($request->input('target_user_ids', []));
        }

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Pengumuman baru berhasil dibuat dan dijadwalkan.');
    }

    /**
     * Tampilkan form edit pengumuman.
     */
    public function edit(Announcement $announcement)
    {
        $announcement->load('targetUsers');

        $units = Unit::where('is_active', true)
            ->with(['users' => function ($q) {
                $q->where('is_active', true)->whereIn('role', ['Supervisor', 'Operator'])->orderBy('role')->orderBy('name');
            }])
            ->orderBy('name')
            ->get();

        $selectedUserIds = $announcement->targetUsers->pluck('id')->toArray();
        $allSupervisorsCount = User::where('is_active', true)->where('role', 'Supervisor')->count();
        $allOperatorsCount = User::where('is_active', true)->where('role', 'Operator')->count();

        return view('admin.announcements.edit', compact('announcement', 'units', 'selectedUserIds', 'allSupervisorsCount', 'allOperatorsCount'));
    }

    /**
     * Perbarui pengumuman di database.
     */
    public function update(Request $request, Announcement $announcement)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:info,warning,danger,success',
            'target_type' => 'required|in:all,all_supervisors,all_operators,specific_users',
            'start_at' => 'required|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'is_active' => 'nullable|boolean',
            'target_user_ids' => 'required_if:target_type,specific_users|array',
            'target_user_ids.*' => 'exists:users,id',
        ], [
            'title.required' => 'Judul pengumuman wajib diisi.',
            'content.required' => 'Isi pesan pengumuman wajib diisi.',
            'start_at.required' => 'Waktu mulai penayangan wajib ditentukan.',
            'end_at.after_or_equal' => 'Waktu selesai harus sama atau setelah waktu mulai.',
            'target_user_ids.required_if' => 'Harap pilih minimal satu pengguna jika sasaran adalah pengguna tertentu.',
        ]);

        $announcement->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'type' => $validated['type'],
            'target_type' => $validated['target_type'],
            'start_at' => $validated['start_at'],
            'end_at' => $validated['end_at'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($validated['target_type'] === 'specific_users') {
            $announcement->targetUsers()->sync($request->input('target_user_ids', []));
        } else {
            $announcement->targetUsers()->detach();
        }

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Pengumuman berhasil diperbarui.');
    }

    /**
     * Hapus pengumuman dari database.
     */
    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Pengumuman berhasil dihapus.');
    }

    /**
     * Sembunyikan atau aktifkan kembali pengumuman (Toggle Is Active).
     */
    public function toggleActive(Announcement $announcement)
    {
        $announcement->update([
            'is_active' => !$announcement->is_active,
        ]);

        $msg = $announcement->is_active
            ? 'Pengumuman berhasil diaktifkan kembali.'
            : 'Pengumuman berhasil disembunyikan dari penayangan.';

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Akhiri penayangan pengumuman secara paksa seketika (Force End).
     */
    public function forceEnd(Announcement $announcement)
    {
        $announcement->update([
            'end_at' => now(),
        ]);

        return redirect()->back()
            ->with('success', 'Penayangan pengumuman berhasil diakhiri sekarang.');
    }
}
