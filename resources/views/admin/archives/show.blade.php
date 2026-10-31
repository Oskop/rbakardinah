<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-xl text-slate-800 leading-tight flex items-center gap-2">
                    <span>🔍</span>
                    <span>Detail Snapshot Arsip: {{ $archive->label }}</span>
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Arsip ID #{{ $archive->id }} • Model {{ class_basename($archive->model_type) }} (Original ID #{{ $archive->model_id }})
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.archives.index') }}"
                    class="inline-flex items-center px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors shadow-sm">
                    ← Kembali ke Arsip
                </a>
                @if(!$archive->isRestored())
                    <form action="{{ route('admin.archives.restore', $archive) }}" method="POST"
                        onsubmit="return confirm('Apakah Anda yakin ingin MEMULIHKAN 100% data {{ addslashes($archive->label) }} kembali ke tabel operasional?');">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-colors shadow-sm">
                            <span>♻️ Pulihkan Data Sekarang</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 rounded-xl shadow-sm text-xs font-medium">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 rounded-xl shadow-sm text-xs font-medium">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Summary Card -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
                <div>
                    <span class="block text-slate-400 font-bold uppercase text-[10px]">Waktu Dihapus</span>
                    <span class="font-bold text-slate-800 text-sm mt-0.5 block">
                        {{ $archive->deleted_at ? $archive->deleted_at->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB' : '-' }}
                    </span>
                    <span class="text-slate-500 text-[11px]">Oleh: {{ $archive->deletedBy?->name ?? 'Admin' }}</span>
                </div>
                <div>
                    <span class="block text-slate-400 font-bold uppercase text-[10px]">Status Pemulihan</span>
                    <span class="mt-0.5 inline-block">
                        @if($archive->isRestored())
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                Sudah Dipulihkan
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                Tersimpan di Arsip Sampah
                            </span>
                        @endif
                    </span>
                    @if($archive->isRestored())
                        <span class="block text-slate-400 text-[10px] mt-1">
                            {{ $archive->restored_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} ({{ $archive->restoredBy?->name ?? 'Admin' }})
                        </span>
                    @endif
                </div>
                <div class="md:col-span-2">
                    <span class="block text-slate-400 font-bold uppercase text-[10px]">Alasan Penghapusan</span>
                    <span class="text-slate-800 font-medium text-xs mt-0.5 block italic bg-slate-50 p-2 rounded-lg border border-slate-100">
                        "{{ $archive->reason ?? 'Tidak ada catatan' }}"
                    </span>
                </div>
            </div>

            <!-- Tab Detail Data Snapshot -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden" x-data="{ tab: 'root' }">
                <div class="flex items-center border-b border-slate-200 bg-slate-50/70 px-4 text-xs font-bold">
                    <button type="button" @click="tab = 'root'"
                        :class="tab === 'root' ? 'border-indigo-600 text-indigo-700 bg-white' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="px-4 py-3 border-b-2 transition-all">
                        📄 Data Root Model ({{ class_basename($archive->model_type) }})
                    </button>
                    <button type="button" @click="tab = 'relations'"
                        :class="tab === 'relations' ? 'border-indigo-600 text-indigo-700 bg-white' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="px-4 py-3 border-b-2 transition-all">
                        🌳 Data Relasi Berjenjang (Related Tree)
                    </button>
                    <button type="button" @click="tab = 'metadata'"
                        :class="tab === 'metadata' ? 'border-indigo-600 text-indigo-700 bg-white' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="px-4 py-3 border-b-2 transition-all">
                        📦 Metadata & File Lampiran
                    </button>
                </div>

                <div class="p-6">
                    <!-- Tab 1: Root Data -->
                    <div x-show="tab === 'root'">
                        <h4 class="text-xs font-bold text-slate-700 uppercase mb-3">Atribut Asli Tabel {{ class_basename($archive->model_type) }}</h4>
                        <div class="bg-slate-900 text-slate-100 p-4 rounded-xl font-mono text-xs overflow-x-auto">
                            <pre>{{ json_encode($archive->root_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </div>

                    <!-- Tab 2: Related Data -->
                    <div x-show="tab === 'relations'">
                        <h4 class="text-xs font-bold text-slate-700 uppercase mb-3">Snapshot Seluruh Tabel Anak</h4>
                        @if(!empty($archive->related_data))
                            <div class="space-y-4">
                                @foreach($archive->related_data as $tableName => $rows)
                                    <div class="border border-slate-200 rounded-xl overflow-hidden">
                                        <div class="bg-slate-100 px-4 py-2 font-bold text-xs text-slate-800 flex justify-between items-center">
                                            <span>📊 Tabel: <code>{{ $tableName }}</code></span>
                                            <span class="px-2 py-0.5 bg-white text-slate-600 rounded-full border border-slate-200 text-[10px]">
                                                {{ count($rows) }} baris
                                            </span>
                                        </div>
                                        <div class="p-3 bg-slate-900 text-slate-100 font-mono text-[11px] max-h-64 overflow-y-auto">
                                            <pre>{{ json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-slate-400 text-xs italic">Tidak ada relasi anak tercatat.</div>
                        @endif
                    </div>

                    <!-- Tab 3: Metadata -->
                    <div x-show="tab === 'metadata'">
                        <h4 class="text-xs font-bold text-slate-700 uppercase mb-3">Daftar File Lampiran & Metadata</h4>
                        <div class="bg-slate-900 text-slate-100 p-4 rounded-xl font-mono text-xs overflow-x-auto">
                            <pre>{{ json_encode($archive->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

