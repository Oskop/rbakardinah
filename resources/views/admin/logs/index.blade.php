<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight flex items-center gap-2">
                    <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    {{ __('Log Data & Riwayat Transaksi Database') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Rekam jejak mutasi data (Create, Update, Delete) oleh seluruh pengguna di semua level sistem.</p>
            </div>
            <div class="text-xs text-gray-400 font-mono bg-gray-100 px-3 py-1.5 rounded-lg border border-gray-200">
                Audit Trail Active
            </div>
        </div>
    </x-slot>

    <div class="py-6" x-data="{
        showModal: false,
        activeLog: null,
        openDetail(log) {
            this.activeLog = log;
            this.showModal = true;
        }
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Summary Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
                    <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center font-bold text-xl">
                        📊
                    </div>
                    <div>
                        <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Transaksi</div>
                        <div class="text-2xl font-black text-gray-800">{{ number_format($totalLogs, 0, ',', '.') }}</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center font-bold text-xl">
                        ⚡
                    </div>
                    <div>
                        <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">Hari Ini</div>
                        <div class="text-2xl font-black text-blue-600">{{ number_format($totalToday, 0, ',', '.') }}</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center font-bold text-xl">
                        ✨
                    </div>
                    <div>
                        <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">Penambahan (Create)</div>
                        <div class="text-2xl font-black text-emerald-600">{{ number_format($totalCreates, 0, ',', '.') }}</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
                    <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center font-bold text-xl">
                        🔄
                    </div>
                    <div>
                        <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">Perubahan / Hapus</div>
                        <div class="text-2xl font-black text-amber-600">
                            {{ number_format($totalUpdates, 0, ',', '.') }} <span class="text-xs text-red-500 font-semibold">/ {{ number_format($totalDeletes, 0, ',', '.') }} Del</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <form method="GET" action="{{ route('admin.logs.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-3 items-end">
                    <!-- Search Input -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Pencarian</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Cari deskripsi, nama, IP..."
                            class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                    </div>

                    <!-- Filter Role -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Peran (Role)</label>
                        <select name="role" class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                            <option value="">Semua Peran</option>
                            <option value="Administrator" {{ request('role') == 'Administrator' ? 'selected' : '' }}>Administrator</option>
                            <option value="Supervisor" {{ request('role') == 'Supervisor' ? 'selected' : '' }}>Supervisor</option>
                            <option value="Operator" {{ request('role') == 'Operator' ? 'selected' : '' }}>Operator</option>
                            <option value="System" {{ request('role') == 'System' ? 'selected' : '' }}>Sistem</option>
                        </select>
                    </div>

                    <!-- Filter Action -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Aksi</label>
                        <select name="action" class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                            <option value="">Semua Aksi</option>
                            <option value="created" {{ request('action') == 'created' ? 'selected' : '' }}>Created (Tambah)</option>
                            <option value="updated" {{ request('action') == 'updated' ? 'selected' : '' }}>Updated (Ubah)</option>
                            <option value="deleted" {{ request('action') == 'deleted' ? 'selected' : '' }}>Deleted (Hapus)</option>
                            <option value="restored" {{ request('action') == 'restored' ? 'selected' : '' }}>Restored (Pulihkan)</option>
                        </select>
                    </div>

                    <!-- Filter Model -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Objek Data</label>
                        <select name="model" class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                            <option value="">Semua Objek</option>
                            @foreach($availableModels as $modelKey => $modelLabel)
                                <option value="{{ $modelKey }}" {{ request('model') == $modelKey ? 'selected' : '' }}>{{ $modelLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-center gap-2">
                        <button type="submit"
                            class="flex-1 inline-flex justify-center items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-sm transition">
                            Filter
                        </button>
                        <a href="{{ route('admin.logs.index') }}"
                            class="inline-flex justify-center items-center px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-lg transition" title="Reset Filter">
                            ✕
                        </a>
                    </div>
                </form>
            </div>

            <!-- Log Table Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-12">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-40">Waktu</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-48">Pengguna</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-28 text-center">Aksi</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-36">Objek</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Deskripsi Transaksi</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-28">IP</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-28">Perubahan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 text-xs">
                            @forelse($logs as $log)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-3 font-mono text-gray-400 text-[11px]">#{{ $log->id }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="font-bold text-gray-800">{{ $log->created_at->format('d/m/Y H:i:s') }}</div>
                                        <div class="text-[10px] text-gray-400">{{ $log->created_at->diffForHumans() }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-900">{{ $log->user_name ?? ($log->user->name ?? 'Sistem') }}</div>
                                        <div class="flex items-center gap-1 mt-0.5">
                                            @php
                                                $roleColor = match($log->user_role) {
                                                    'Administrator' => 'bg-purple-100 text-purple-700 border-purple-200',
                                                    'Supervisor' => 'bg-amber-100 text-amber-700 border-amber-200',
                                                    'Operator' => 'bg-blue-100 text-blue-700 border-blue-200',
                                                    default => 'bg-gray-100 text-gray-600 border-gray-200',
                                                };
                                            @endphp
                                            <span class="px-1.5 py-0.2 text-[9px] font-black uppercase rounded border {{ $roleColor }}">
                                                {{ $log->user_role ?? 'System' }}
                                            </span>
                                            @if($log->user && $log->user->unit)
                                                <span class="text-[10px] text-gray-500 truncate" title="{{ $log->user->unit->name }}">
                                                    • {{ $log->user->unit->code }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        @php
                                            $actionBadge = match($log->action) {
                                                'created' => 'bg-emerald-50 text-emerald-700 border-emerald-300 font-black',
                                                'updated' => 'bg-blue-50 text-blue-700 border-blue-300 font-black',
                                                'deleted' => 'bg-red-50 text-red-700 border-red-300 font-black',
                                                'restored' => 'bg-purple-50 text-purple-700 border-purple-300 font-black',
                                                default => 'bg-gray-50 text-gray-700 border-gray-300 font-semibold',
                                            };
                                        @endphp
                                        <span class="inline-block px-2.5 py-1 text-[10px] uppercase rounded-full border {{ $actionBadge }}">
                                            {{ $log->action }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="font-mono font-semibold text-gray-700 bg-gray-100 px-1.5 py-0.5 rounded text-[10px]">
                                            {{ $log->model_base_name }}
                                        </span>
                                        @if($log->model_id)
                                            <span class="text-gray-400 text-[10px]">#{{ $log->model_id }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-800">
                                        {{ $log->description ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 font-mono text-[10px] text-gray-500 whitespace-nowrap">
                                        {{ $log->ip_address ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        @if(!empty($log->old_values) || !empty($log->new_values))
                                            <button type="button"
                                                @click="openDetail({{ json_encode($log) }})"
                                                class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded text-[10px] font-bold transition">
                                                🔍 Detail Diff
                                            </button>
                                        @else
                                            <span class="text-gray-400 text-[10px]">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-12 text-center text-gray-400">
                                        <div class="text-4xl mb-2">📜</div>
                                        <div class="font-semibold text-sm text-gray-600">Belum ada riwayat aktivitas transaksi yang tercatat.</div>
                                        <div class="text-xs text-gray-400 mt-1">Seluruh mutasi data database otomatis akan tercatat di sini.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($logs->hasPages())
                    <div class="p-4 border-t border-gray-100 bg-gray-50">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>

        </div>

        <!-- Interactive JSON Diff Modal -->
        <div x-show="showModal" style="display: none;"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Backdrop -->
                <div x-show="showModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @click="showModal = false"
                    class="fixed inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm transition-opacity"></div>

                <!-- Modal Panel -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div x-show="showModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-gray-200">
                    
                    <div class="bg-gradient-to-r from-gray-900 to-indigo-900 px-6 py-4 flex items-center justify-between text-white">
                        <div class="flex items-center gap-3">
                            <span class="text-xl">🔍</span>
                            <div>
                                <h3 class="font-bold text-base" x-text="'Detail Perubahan Nilai Data #' + (activeLog ? activeLog.id : '')"></h3>
                                <p class="text-xs text-gray-300" x-text="activeLog ? (activeLog.description || activeLog.model_type) : ''"></p>
                            </div>
                        </div>
                        <button type="button" @click="showModal = false" class="text-gray-400 hover:text-white text-2xl font-bold">×</button>
                    </div>

                    <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto text-xs">
                        <!-- Log Meta Info -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 bg-gray-50 p-3 rounded-xl border border-gray-200">
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Pengguna:</span>
                                <span class="font-bold text-gray-800" x-text="activeLog ? (activeLog.user_name + ' (' + activeLog.user_role + ')') : ''"></span>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Aksi:</span>
                                <span class="font-bold uppercase text-indigo-600" x-text="activeLog ? activeLog.action : ''"></span>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Objek:</span>
                                <span class="font-bold text-gray-800" x-text="activeLog ? (activeLog.model_type + ' #' + activeLog.model_id) : ''"></span>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">IP & User Agent:</span>
                                <span class="font-mono text-gray-600 text-[10px]" x-text="activeLog ? activeLog.ip_address : ''"></span>
                            </div>
                        </div>

                        <!-- Diff Viewer -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Old Values -->
                            <div class="border border-red-200 rounded-xl overflow-hidden bg-red-50/20">
                                <div class="bg-red-100 text-red-800 px-4 py-2 font-bold flex items-center justify-between">
                                    <span>🔻 Nilai Sebelum (Old Values)</span>
                                </div>
                                <div class="p-4 font-mono text-[11px] overflow-x-auto max-h-96">
                                    <template x-if="activeLog && activeLog.old_values && Object.keys(activeLog.old_values).length > 0">
                                        <pre class="text-red-900 whitespace-pre-wrap" x-text="JSON.stringify(activeLog.old_values, null, 2)"></pre>
                                    </template>
                                    <template x-if="!activeLog || !activeLog.old_values || Object.keys(activeLog.old_values).length === 0">
                                        <span class="text-gray-400 italic">Tidak ada nilai sebelumnya (data baru/create).</span>
                                    </template>
                                </div>
                            </div>

                            <!-- New Values -->
                            <div class="border border-emerald-200 rounded-xl overflow-hidden bg-emerald-50/20">
                                <div class="bg-emerald-100 text-emerald-800 px-4 py-2 font-bold flex items-center justify-between">
                                    <span>🔺 Nilai Sesudah (New Values)</span>
                                </div>
                                <div class="p-4 font-mono text-[11px] overflow-x-auto max-h-96">
                                    <template x-if="activeLog && activeLog.new_values && Object.keys(activeLog.new_values).length > 0">
                                        <pre class="text-emerald-900 whitespace-pre-wrap" x-text="JSON.stringify(activeLog.new_values, null, 2)"></pre>
                                    </template>
                                    <template x-if="!activeLog || !activeLog.new_values || Object.keys(activeLog.new_values).length === 0">
                                        <span class="text-gray-400 italic">Tidak ada nilai baru (data dihapus/delete).</span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-3 border-t border-gray-200 flex justify-end">
                        <button type="button" @click="showModal = false"
                            class="px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white rounded-lg text-xs font-bold transition">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
