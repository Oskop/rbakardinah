<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
                <span>📑</span>
                <span>{{ __('RBA Header (Global Budget Containers)') }}</span>
            </h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.archives.index') }}"
                    class="inline-flex items-center px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-md font-semibold text-xs transition-colors shadow-2xs">
                    <span>🗄️</span> <span class="ml-1.5">Arsip Sampah & Restore</span>
                </a>
                <a href="{{ route('admin.headers.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
                    <span>➕</span> <span class="ml-1.5">Inisialisasi RBA Baru</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
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

            @if(\App\Models\SystemSetting::isRbaDeletionAllowed())
                <div class="p-4 bg-amber-50 border-l-4 border-amber-500 text-amber-900 rounded-xl shadow-sm text-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="text-base">⚠️</span>
                        <span><strong>Mode Pembersihan Aktif:</strong> Tombol "Hapus RBA" saat ini sedang ditampilkan. Harap berhati-hati dan segera kunci kembali setelah selesai membersihkan data yang keliru.</span>
                    </div>
                    <a href="{{ route('admin.settings.index') }}" class="font-bold underline text-amber-950 hover:text-black whitespace-nowrap">
                        Ke Pengaturan Sistem &rarr;
                    </a>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200/80">
                <div class="p-6 text-gray-900">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50/80">
                            <tr>
                                <th
                                    class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Tahun Anggaran</th>
                                <th
                                    class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Oleh</th>
                                <th
                                    class="px-6 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-xs">
                            @forelse($headers as $header)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-900">
                                        {{ $header->year }} - {{ $header->period->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2.5 py-1 inline-flex text-xs leading-4 font-bold rounded-full {{ $header->status_global === 'Locked' ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-blue-100 text-blue-800 border border-blue-200' }}">
                                            {{ $header->status_global }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                        {{ $header->admin->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right font-medium">
                                        <div class="flex justify-end items-center space-x-2">
                                            <!-- Toggle Lock Status -->
                                            <form action="{{ route('admin.headers.toggle-status', $header) }}" method="POST" onsubmit="return confirm('Ubah status kunci untuk RBA ini?')">
                                                @csrf
                                                @if($header->status_global === 'Draft')
                                                    <button type="submit" class="text-amber-700 hover:text-amber-900 border border-amber-200 px-2.5 py-1 rounded-lg bg-amber-50 font-bold transition-colors">Lock RBA</button>
                                                @else
                                                    <button type="submit" class="text-emerald-700 hover:text-emerald-900 border border-emerald-200 px-2.5 py-1 rounded-lg bg-emerald-50 font-bold transition-colors">Unlock RBA</button>
                                                @endif
                                            </form>

                                            <!-- View Submissions -->
                                            <a href="{{ route('admin.headers.show', $header) }}"
                                                class="text-indigo-600 hover:text-indigo-900 border border-indigo-200 px-2.5 py-1 rounded-lg bg-indigo-50 font-bold transition-colors">
                                                Lihat Data
                                            </a>

                                            @if(\App\Models\SystemSetting::isRbaDeletionAllowed())
                                                <!-- Hapus RBA ke Arsip Sampah -->
                                                <form action="{{ route('admin.headers.destroy', $header) }}" method="POST"
                                                    onsubmit="const r = prompt('⚠️ KONFIRMASI PENGHAPUSAN RBA:\n\nAnda akan menghapus permanen RBA {{ $header->year }} - {{ $header->period->name }} dari data operasional.\nData lengkap beserta seluruh relasi akan diamankan ke Arsip Sampah dan dapat dipulihkan kapan saja.\n\nKetik alasan penghapusan di bawah ini:'); if (!r) return false; this.reason.value = r; return true;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="reason" value="">
                                                    <button type="submit" class="text-rose-600 hover:text-rose-900 border border-rose-200 px-2.5 py-1 rounded-lg bg-rose-50 font-bold transition-colors cursor-pointer" title="Hapus ke Arsip Sampah">
                                                        Hapus RBA
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 whitespace-nowrap text-center text-gray-400">
                                        Belum ada inisialisasi RBA.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
