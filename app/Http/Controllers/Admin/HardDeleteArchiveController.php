<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HardDeleteArchive;
use App\Services\HardDeleteArchiveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class HardDeleteArchiveController extends Controller
{
    /**
     * Display a listing of archived hard deleted records.
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');

        $query = HardDeleteArchive::with(['deletedBy', 'restoredBy'])->latest();

        if ($status === 'unrestored') {
            $query->unrestored();
        } elseif ($status === 'restored') {
            $query->restored();
        }

        $archives = $query->paginate(15)->withQueryString();

        return view('admin.archives.index', compact('archives', 'status'));
    }

    /**
     * Display the specified archive snapshot details.
     */
    public function show(HardDeleteArchive $archive)
    {
        $archive->load(['deletedBy', 'restoredBy']);
        return view('admin.archives.show', compact('archive'));
    }

    /**
     * Restore the archived record back to operational tables.
     */
    public function restore(HardDeleteArchive $archive, HardDeleteArchiveService $archiveService)
    {
        try {
            if ($archive->model_type === \App\Models\RbaHeader::class) {
                $restored = $archiveService->restoreRbaHeader($archive, Auth::id());
                return redirect()->route('admin.headers.show', $restored->id)
                    ->with('success', "Sukses! Data {$archive->label} berhasil dipulihkan 100% ke tabel operasional.");
            }

            return back()->with('error', "Model type [{$archive->model_type}] belum didukung untuk auto-restore.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

