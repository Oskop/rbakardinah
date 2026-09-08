<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PerformanceIndicator;
use App\Models\PerformanceIndicatorTarget;
use App\Models\PerformanceIndicatorTargetHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerformanceIndicatorController extends Controller
{
    /**
     * Tampilkan matriks indikator kinerja dan target 5 tahunan.
     */
    public function index(Request $request)
    {
        $currentYear = now()->year;
        $startYear = (int) $request->input('start_year', $currentYear);
        $status = $request->input('status', 'all');
        $search = trim($request->input('search', ''));

        // 5 tahun berturut-turut
        $years = range($startYear, $startYear + 4);

        $query = PerformanceIndicator::query()
            ->with(['targets' => function ($q) use ($years) {
                $q->whereIn('year', $years)->with('histories');
            }, 'creator']);

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $indicators = $query->orderBy('order')
            ->orderBy('code')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        // Opsi pilihan tahun awal (5 tahun sebelum hingga 10 tahun ke depan)
        $availableYears = range($currentYear - 5, $currentYear + 10);

        // Ringkasan status untuk tab/filter
        $totalCount = PerformanceIndicator::count();
        $activeCount = PerformanceIndicator::where('is_active', true)->count();
        $inactiveCount = PerformanceIndicator::where('is_active', false)->count();

        return view('admin.performance-indicators.index', compact(
            'indicators',
            'startYear',
            'years',
            'status',
            'search',
            'availableYears',
            'totalCount',
            'activeCount',
            'inactiveCount'
        ));
    }

    /**
     * Simpan indikator kinerja parent baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'unit' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['order'] = $validated['order'] ?? 0;
        $validated['is_active'] = $request->has('is_active') ? (bool) $request->input('is_active') : true;
        $validated['created_by'] = auth()->id();

        $indicator = PerformanceIndicator::create($validated);

        return redirect()->back()
            ->with('success', "Indikator kinerja '{$indicator->name}' berhasil ditambahkan.");
    }

    /**
     * Perbarui data indikator kinerja parent.
     */
    public function update(Request $request, PerformanceIndicator $performanceIndicator)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'unit' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['order'] = $validated['order'] ?? 0;
        $validated['is_active'] = $request->has('is_active') ? (bool) $request->input('is_active') : false;

        $performanceIndicator->update($validated);

        return redirect()->back()
            ->with('success', "Indikator kinerja '{$performanceIndicator->name}' berhasil diperbarui.");
    }

    /**
     * Toggle status aktif/nonaktif indikator kinerja.
     */
    public function toggleStatus(PerformanceIndicator $performanceIndicator)
    {
        $performanceIndicator->update([
            'is_active' => !$performanceIndicator->is_active,
        ]);

        $msg = $performanceIndicator->is_active
            ? "Indikator kinerja '{$performanceIndicator->name}' berhasil diaktifkan kembali."
            : "Indikator kinerja '{$performanceIndicator->name}' berhasil dinonaktifkan.";

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Hapus indikator kinerja (menggunakan Soft Delete).
     */
    public function destroy(PerformanceIndicator $performanceIndicator)
    {
        $name = $performanceIndicator->name;

        DB::transaction(function () use ($performanceIndicator) {
            // Soft delete target terkait
            $performanceIndicator->targets()->delete();
            // Soft delete indikator
            $performanceIndicator->delete();
        });

        return redirect()->back()
            ->with('success', "Indikator kinerja '{$name}' dan data targetnya berhasil dihapus (soft delete).");
    }

    /**
     * Simpan atau perbarui nilai target pada tahun tertentu dengan pencatatan versi histori.
     */
    public function updateTarget(Request $request, PerformanceIndicator $performanceIndicator, int $year)
    {
        $validated = $request->validate([
            'target_value' => 'required|string|max:100',
            'change_note' => 'nullable|string',
        ]);

        $newTargetValue = trim($validated['target_value']);
        $changeNote = !empty($validated['change_note']) ? trim($validated['change_note']) : null;
        $userId = auth()->id();

        DB::transaction(function () use ($performanceIndicator, $year, $newTargetValue, $changeNote, $userId) {
            $target = PerformanceIndicatorTarget::withTrashed()
                ->where('performance_indicator_id', $performanceIndicator->id)
                ->where('year', $year)
                ->first();

            if ($target) {
                // Restore jika sebelumnya sempat di-soft delete
                if ($target->trashed()) {
                    $target->restore();
                }

                $oldValue = $target->target_value;

                // Hanya catat versi baru jika nilainya berubah atau belum ada riwayat sama sekali
                if ($oldValue !== $newTargetValue) {
                    $newVersion = $target->current_version + 1;

                    $target->update([
                        'target_value' => $newTargetValue,
                        'current_version' => $newVersion,
                        'updated_by' => $userId,
                    ]);

                    PerformanceIndicatorTargetHistory::create([
                        'target_id' => $target->id,
                        'performance_indicator_id' => $performanceIndicator->id,
                        'year' => $year,
                        'version_number' => $newVersion,
                        'old_value' => $oldValue,
                        'new_value' => $newTargetValue,
                        'change_note' => $changeNote ?: 'Pembaruan nilai target tahunan',
                        'user_id' => $userId,
                    ]);
                }
            } else {
                // Input target baru (Versi 1)
                $target = PerformanceIndicatorTarget::create([
                    'performance_indicator_id' => $performanceIndicator->id,
                    'year' => $year,
                    'target_value' => $newTargetValue,
                    'current_version' => 1,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                PerformanceIndicatorTargetHistory::create([
                    'target_id' => $target->id,
                    'performance_indicator_id' => $performanceIndicator->id,
                    'year' => $year,
                    'version_number' => 1,
                    'old_value' => null,
                    'new_value' => $newTargetValue,
                    'change_note' => $changeNote ?: 'Penetapan target awal tahun ' . $year,
                    'user_id' => $userId,
                ]);
            }
        });

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Target tahun {$year} berhasil disimpan.",
            ]);
        }

        return redirect()->back()
            ->with('success', "Target tahun {$year} untuk '{$performanceIndicator->name}' berhasil disimpan.");
    }

    /**
     * Batch simpan 5 target tahunan sekaligus untuk 1 indikator.
     */
    public function batchUpdateTargets(Request $request, PerformanceIndicator $performanceIndicator)
    {
        $validated = $request->validate([
            'targets' => 'required|array',
            'targets.*' => 'nullable|string|max:100',
            'change_note' => 'nullable|string',
        ]);

        $targetsData = $validated['targets'];
        $changeNote = $validated['change_note'] ?? 'Penyesuaian target berkala';
        $userId = auth()->id();

        DB::transaction(function () use ($performanceIndicator, $targetsData, $changeNote, $userId) {
            foreach ($targetsData as $year => $value) {
                if ($value === null || trim($value) === '') {
                    continue;
                }

                $year = (int) $year;
                $value = trim($value);

                $target = PerformanceIndicatorTarget::withTrashed()
                    ->where('performance_indicator_id', $performanceIndicator->id)
                    ->where('year', $year)
                    ->first();

                if ($target) {
                    if ($target->trashed()) {
                        $target->restore();
                    }

                    if ($target->target_value !== $value) {
                        $oldVal = $target->target_value;
                        $newVer = $target->current_version + 1;

                        $target->update([
                            'target_value' => $value,
                            'current_version' => $newVer,
                            'updated_by' => $userId,
                        ]);

                        PerformanceIndicatorTargetHistory::create([
                            'target_id' => $target->id,
                            'performance_indicator_id' => $performanceIndicator->id,
                            'year' => $year,
                            'version_number' => $newVer,
                            'old_value' => $oldVal,
                            'new_value' => $value,
                            'change_note' => $changeNote,
                            'user_id' => $userId,
                        ]);
                    }
                } else {
                    $target = PerformanceIndicatorTarget::create([
                        'performance_indicator_id' => $performanceIndicator->id,
                        'year' => $year,
                        'target_value' => $value,
                        'current_version' => 1,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);

                    PerformanceIndicatorTargetHistory::create([
                        'target_id' => $target->id,
                        'performance_indicator_id' => $performanceIndicator->id,
                        'year' => $year,
                        'version_number' => 1,
                        'old_value' => null,
                        'new_value' => $value,
                        'change_note' => $changeNote,
                        'user_id' => $userId,
                    ]);
                }
            }
        });

        return redirect()->back()
            ->with('success', "Target tahunan untuk '{$performanceIndicator->name}' berhasil diperbarui.");
    }

    /**
     * Ambil data riwayat versi target untuk modal dialog (JSON).
     */
    public function targetHistory(PerformanceIndicator $performanceIndicator, int $year)
    {
        $target = PerformanceIndicatorTarget::where('performance_indicator_id', $performanceIndicator->id)
            ->where('year', $year)
            ->first();

        if (!$target) {
            return response()->json([
                'success' => false,
                'message' => 'Target belum pernah diinput.',
                'histories' => [],
            ], 404);
        }

        $histories = PerformanceIndicatorTargetHistory::where('target_id', $target->id)
            ->with('user:id,name,role')
            ->orderBy('version_number', 'desc')
            ->get()
            ->map(function ($h) {
                return [
                    'id' => $h->id,
                    'version_number' => $h->version_number,
                    'old_value' => $h->old_value,
                    'new_value' => $h->new_value,
                    'change_note' => $h->change_note,
                    'user_name' => $h->user ? $h->user->name : 'Sistem / Administrator',
                    'created_at_formatted' => $h->created_at ? $h->created_at->translatedFormat('d M Y, H:i') . ' WIB' : '-',
                ];
            });

        return response()->json([
            'success' => true,
            'indicator_name' => $performanceIndicator->name,
            'indicator_code' => $performanceIndicator->code,
            'year' => $year,
            'current_value' => $target->target_value,
            'current_version' => $target->current_version,
            'histories' => $histories,
        ]);
    }
}
