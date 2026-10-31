<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-xl text-slate-800 leading-tight flex items-center gap-2">
                    <span>🗄️</span>
                    <span>{{ __('Arsip Data Terhapus (Recycle Bin & Safe Restore)') }}</span>
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Seluruh data yang dihapus secara permanen dari tabel operasional diamankan di sini sebagai snapshot utuh dan dapat dipulihkan 100% sewaktu-waktu.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.headers.index') }}"
                    class="inline-flex items-center px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors shadow-sm">
                    ← Kembali ke RBA Headers
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 rounded-xl shadow-sm text-xs font-medium flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">✅</span>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 rounded-xl shadow-sm text-xs font-medium flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">⚠️</span>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            <!-- Info Alert untuk Audit BPK -->
            <div class="bg-indigo-50/70 border border-indigo-200 rounded-2xl p-4 text-xs text-indigo-900 flex items-start gap-3">
                <span class="text-xl">ℹ️</span>
                <div class="leading-relaxed">
                    <strong>Pusat Audit Trail & Jaring Pengaman:</strong> Seluruh penghapusan RBA Header atau entitas penting disimpan dalam bentuk snapshot berstruktur di tabel arsip ini. Hal ini memastikan tabel operasional bebas dari data salah/duplikat, namun riwayat penghapusan tetap dapat dipertanggungjawabkan kepada BPK dan siap dipulihkan seketika jika terjadi kebutuhan darurat.
                </div>
            </div>

            <!-- Filter Status Tab -->
            <div class="flex items-center gap-2 border-b border-slate-200 pb-3">
                <a href="{{ route('admin.archives.index', ['status' => 'all']) }}"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all {{ $status === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
                    Semua Arsip ({{ $archives->total() }})
                </a>
                <a href="{{ route('admin.archives.index', ['status' => 'unrestored']) }}"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all {{ $status === 'unrestored' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
                    Siap Dipulihkan (Belum Restore)
                </a>
                <a href="{{ route('admin.archives.index', ['status' => 'restored']) }}"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all {{ $status === 'restored' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
                    Sudah Dipulihkan
                </a>
            </div>

            <!-- Table Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200/80">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-left text-xs">
                            <thead class="bg-slate-50/80 text-slate-600 font-bold uppercase tracking-wider">
                                <tr>
                                    <th class="px-5 py-3.5">ID Arsip</th>
                                    <th class="px-5 py-3.5">Objek & Data Asli</th>
                                    <th class="px-5 py-3.5">Alasan Penghapusan</th>
                                    <th class="px-5 py-3.5">Dihapus Oleh</th>
                                    <th class="px-5 py-3.5">Status Pemulihan</th>
                                    <th class="px-5 py-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse($archives as $archive)
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="px-5 py-4 font-mono font-bold text-slate-500">
                                            #{{ $archive->id }}
                                        </td>
                                        <td class="px-5 py-4">
                                            <div class="font-bold text-slate-900 text-xs">
                                                {{ $archive->label }}
                                            </div>
                                            <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                                Model: {{ class_basename($archive->model_type) }} | Original ID: #{{ $archive->model_id }}
                                            </div>
                                            @if(!empty($archive->metadata))
                                                <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                                    @if(isset($archive->metadata['submissions_count']))
                                                        <span class="px-1.5 py-0.5 bg-slate-100 text-slate-600 rounded font-semibold text-[9px] border border-slate-200">
                                                            🏢 {{ $archive->metadata['submissions_count'] }} Submissions
                                                        </span>
                                                    @endif
                                                    @if(isset($archive->metadata['details_count']))
                                                        <span class="px-1.5 py-0.5 bg-slate-100 text-slate-600 rounded font-semibold text-[9px] border border-slate-200">
                                                            📝 {{ $archive->metadata['details_count'] }} Details
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 max-w-xs text-slate-700 italic">
                                            "{{ $archive->reason ?? 'Tanpa alasan tercatat' }}"
                                        </td>
                                        <td class="px-5 py-4">
                                            <div class="font-medium text-slate-800">
                                                👤 {{ $archive->deletedBy?->name ?? 'Sistem/Admin' }}
                                            </div>
                                            <div class="text-[10px] text-slate-400 mt-0.5">
                                                {{ $archive->deleted_at ? $archive->deleted_at->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB' : '-' }}
                                            </div>
                                        </td>
                                        <td class="px-5 py-4">
                                            @if($archive->isRestored())
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                    <span>✓</span> <span>Sudah Dipulihkan</span>
                                                </span>
                                                <div class="text-[9px] text-slate-400 mt-1">
                                                    Oleh: {{ $archive->restoredBy?->name ?? 'Admin' }}<br>
                                                    {{ $archive->restored_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}
                                                </div>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                                    <span>📦</span> <span>Di Arsip Sampah</span>
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 text-right whitespace-nowrap">
                                            <div class="inline-flex items-center gap-2 justify-end">
                                                <a href="{{ route('admin.archives.show', $archive) }}"
                                                    class="inline-flex items-center px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition-colors"
                                                    title="Lihat Detail Snapshot">
                                                    <span>🔍 Detail</span>
                                                </a>

                                                @if(!$archive->isRestored())
                                                    <form action="{{ route('admin.archives.restore', $archive) }}" method="POST"
                                                        onsubmit="return confirm('Apakah Anda yakin ingin MEMULIHKAN 100% data {{ addslashes($archive->label) }} kembali ke tabel operasional?');">
                                                        @csrf
                                                        <button type="submit"
                                                            class="inline-flex items-center px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition-colors shadow-2xs">
                                                            <span>♻️ Pulihkan</span>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center text-slate-400 text-xs">
                                            <span class="text-3xl block mb-2">📭</span>
                                            Tidak ada rekaman data di arsip sampah saat ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($archives->hasPages())
                        <div class="mt-4 pt-3 border-t border-slate-100">
                            {{ $archives->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

