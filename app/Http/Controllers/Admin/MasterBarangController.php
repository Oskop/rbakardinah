<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterBarang;
use Illuminate\Http\Request;

class MasterBarangController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $draw = (int) $request->input('draw', 1);
            $start = max((int) $request->input('start', 0), 0);
            $length = min(max((int) $request->input('length', 10), 1), 100);

            $query = MasterBarang::query();
            $recordsTotal = MasterBarang::count();

            // Filter Kategori Level 5 (Rincian Objek)
            $level5 = trim((string) $request->input('level5', ''));
            if ($level5 !== '') {
                $query->where('kode_level5', $level5);
            }

            // Filter Kategori Level 6 (Sub Rincian Objek)
            $level6 = trim((string) $request->input('level6', ''));
            if ($level6 !== '') {
                $query->where('kode_level6', $level6);
            }

            // Smart Search (space-insensitive & multi-attribute)
            $searchValue = trim((string) $request->input('search.value', ''));
            if ($searchValue !== '') {
                $cleanSearch = str_replace([' ', '-', '_', '.', '/'], '', strtolower($searchValue));
                $query->where(function ($q) use ($searchValue, $cleanSearch) {
                    $q->where('nama_barang', 'like', "%{$searchValue}%")
                      ->orWhereRaw("REPLACE(REPLACE(nama_barang, ' ', ''), '-', '') LIKE ?", ["%{$cleanSearch}%"])
                      ->orWhere('kode_barang', 'like', "%{$searchValue}%")
                      ->orWhere('kode_permendagri', 'like', "%{$searchValue}%")
                      ->orWhere('nama_level5', 'like', "%{$searchValue}%")
                      ->orWhere('nama_level6', 'like', "%{$searchValue}%")
                      ->orWhere('satuan', 'like', "%{$searchValue}%")
                      ->orWhere('deskripsi', 'like', "%{$searchValue}%");
                });
            }

            $recordsFiltered = $query->count();

            // Pemetaan kolom pengurutan
            $columnsMap = [
                0 => 'kode_barang',
                1 => 'nama_barang',
                2 => 'satuan',
                3 => 'is_active',
            ];

            $orderColumnIndex = (int) $request->input('order.0.column', 0);
            $orderDirection = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
            $orderColumn = $columnsMap[$orderColumnIndex] ?? 'id';

            $items = $query->orderBy($orderColumn, $orderDirection)
                ->skip($start)
                ->take($length)
                ->get();

            $csrf = csrf_token();

            $data = $items->map(function ($item) use ($csrf) {
                // Kolom 0: Kode Barang & Kode Permendagri 108
                $kodeHtml = '<div>' . e($item->kode_barang) . '</div>';
                if ($item->kode_permendagri) {
                    $kodeHtml .= '<div class="text-[10px] text-slate-500 font-normal mt-0.5" title="Kodefikasi Referensi Permendagri No. 108 Tahun 2016">'
                        . '<span class="inline-block px-1.5 py-0.5 bg-slate-100 text-slate-600 rounded text-[9px] font-semibold border border-slate-200">Ref 108: ' . e($item->kode_permendagri) . '</span>'
                        . '</div>';
                }

                // Kolom 1: Nama Barang & Badge Kategori Level 5 / Level 6
                $namaHtml = '<div class="text-sm font-semibold text-gray-900 leading-snug">' . e($item->nama_barang) . '</div>';
                if ($item->nama_level5 || $item->nama_level6) {
                    $namaHtml .= '<div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1.5 flex-wrap">';
                    if ($item->nama_level5) {
                        $namaHtml .= '<span class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-blue-50 text-blue-700 rounded border border-blue-200 text-[9px] font-semibold" title="Rincian Objek (Level 5)">'
                            . '<span class="text-[10px]">📁</span> ' . e($item->nama_level5)
                            . '</span>';
                    }
                    if ($item->nama_level5 && $item->nama_level6) {
                        $namaHtml .= '<span class="text-slate-300 font-bold">›</span>';
                    }
                    if ($item->nama_level6) {
                        $namaHtml .= '<span class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-indigo-50 text-indigo-700 rounded border border-indigo-200 text-[9px] font-semibold" title="Sub Rincian Objek (Level 6)">'
                            . '<span class="text-[10px]">📂</span> ' . e($item->nama_level6)
                            . '</span>';
                    }
                    $namaHtml .= '</div>';
                } elseif ($item->deskripsi) {
                    $namaHtml .= '<div class="text-xs text-gray-500 mt-0.5 line-clamp-1">' . e($item->deskripsi) . '</div>';
                }

                // Kolom 2: Satuan
                $satuanHtml = e($item->satuan ?? '-');

                // Kolom 3: Status Badge
                $statusBadgeClass = $item->is_active
                    ? 'bg-green-100 text-green-800 border border-green-200'
                    : 'bg-gray-100 text-gray-600 border border-gray-200';
                $statusText = $item->is_active ? 'Active' : 'Inactive';
                $statusHtml = '<span class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-full ' . $statusBadgeClass . '">' . $statusText . '</span>';

                // Kolom 4: Aksi (Edit & Activate/Deactivate)
                $editUrl = route('admin.master-barangs.edit', $item);
                $destroyUrl = route('admin.master-barangs.destroy', $item);
                $actionConfirm = $item->is_active ? 'menonaktifkan' : 'mengaktifkan';
                $actionBtnClass = $item->is_active ? 'text-red-600 hover:text-red-900 font-semibold' : 'text-green-600 hover:text-green-900 font-semibold';
                $actionBtnLabel = $item->is_active ? 'Deactivate' : 'Activate';

                $actionsHtml = '<a href="' . $editUrl . '" class="text-indigo-600 hover:text-indigo-900 font-semibold transition-colors">Edit</a> '
                    . '<form action="' . $destroyUrl . '" method="POST" class="inline-block" onsubmit="return confirm(\'Apakah Anda yakin ingin ' . $actionConfirm . ' master barang ini?\');">'
                    . '<input type="hidden" name="_token" value="' . $csrf . '">'
                    . '<input type="hidden" name="_method" value="DELETE">'
                    . '<button type="submit" class="' . $actionBtnClass . '">' . $actionBtnLabel . '</button>'
                    . '</form>';

                return [
                    'kode_barang' => $kodeHtml,
                    'nama_barang' => $namaHtml,
                    'satuan' => $satuanHtml,
                    'is_active' => $statusHtml,
                    'actions' => $actionsHtml,
                ];
            });

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data,
            ]);
        }

        // Ambil daftar unik Level 5 (Rincian Objek) untuk dropdown filter
        $level5List = MasterBarang::whereNotNull('kode_level5')
            ->select('kode_level5', 'nama_level5')
            ->distinct()
            ->orderBy('nama_level5')
            ->get();

        return view('admin.master_barangs.index', compact('level5List'));
    }

    /**
     * Endpoint API untuk mengambil daftar Sub Rincian Objek (Level 6) berdasarkan Level 5
     */
    public function getLevel6(Request $request)
    {
        $level5 = trim((string) $request->input('level5', ''));
        $query = MasterBarang::whereNotNull('kode_level6');

        if ($level5 !== '') {
            $query->where('kode_level5', $level5);
        }

        $items = $query->select('kode_level6', 'nama_level6')
            ->distinct()
            ->orderBy('nama_level6')
            ->get();

        return response()->json($items);
    }

    public function create()
    {
        return view('admin.master_barangs.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_barang' => 'required|string|max:100|unique:master_barangs,kode_barang',
            'kode_permendagri' => 'nullable|string|max:50',
            'kode_level5' => 'nullable|string|max:20',
            'nama_level5' => 'nullable|string|max:255',
            'kode_level6' => 'nullable|string|max:30',
            'nama_level6' => 'nullable|string|max:255',
            'nama_barang' => 'required|string|max:255',
            'satuan' => 'nullable|string|max:50',
            'deskripsi' => 'nullable|string',
        ]);

        $validated['is_active'] = true;

        MasterBarang::create($validated);

        return redirect()->route('admin.master-barangs.index')
            ->with('success', 'Master Barang BMD Permendagri 108 berhasil ditambahkan.');
    }

    public function edit(MasterBarang $masterBarang)
    {
        return view('admin.master_barangs.edit', compact('masterBarang'));
    }

    public function update(Request $request, MasterBarang $masterBarang)
    {
        $validated = $request->validate([
            'kode_barang' => 'required|string|max:100|unique:master_barangs,kode_barang,' . $masterBarang->id,
            'kode_permendagri' => 'nullable|string|max:50',
            'kode_level5' => 'nullable|string|max:20',
            'nama_level5' => 'nullable|string|max:255',
            'kode_level6' => 'nullable|string|max:30',
            'nama_level6' => 'nullable|string|max:255',
            'nama_barang' => 'required|string|max:255',
            'satuan' => 'nullable|string|max:50',
            'deskripsi' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $masterBarang->update($validated);

        return redirect()->route('admin.master-barangs.index')
            ->with('success', 'Master Barang BMD berhasil diperbarui.');
    }

    public function destroy(MasterBarang $masterBarang)
    {
        $masterBarang->update(['is_active' => !$masterBarang->is_active]);

        $status = $masterBarang->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('admin.master-barangs.index')
            ->with('success', "Master Barang BMD berhasil {$status}.");
    }
}
