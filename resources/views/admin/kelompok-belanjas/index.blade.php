<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Manajemen Kelompok Belanja') }}
            </h2>
            <a href="{{ route('admin.kelompok-belanja.create') }}"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
                <span>➕</span> <span class="ml-1.5">Tambah Kelompok Belanja</span>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded-lg shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-lg shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Dedicated Column Filter Toolbar -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5">
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-800">Filter Kolom Kelompok Belanja</h3>
                            <p class="text-[11px] text-slate-400">Saring kelompok belanja berdasarkan kriteria spesifik di bawah ini</p>
                        </div>
                    </div>
                    <button type="button" id="btn-reset-filters"
                        class="text-xs text-indigo-600 hover:text-indigo-800 font-bold hover:underline inline-flex items-center gap-1.5 transition-colors">
                        <span>🔄</span>
                        <span>Reset Semua Filter</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Filter 1: Status Kelompok Belanja -->
                    <div>
                        <label for="filter-status" class="block text-xs font-bold text-slate-700 mb-1.5">Status Kelompok Belanja</label>
                        <select id="filter-status" class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                            <option value="">Semua Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <!-- Filter 2: Keterikatan Rekening -->
                    <div>
                        <label for="filter-accounts" class="block text-xs font-bold text-slate-700 mb-1.5">Keterikatan Rekening Belanja</label>
                        <select id="filter-accounts" class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                            <option value="">Semua Kelompok</option>
                            <option value="Ada Rekening">Memiliki Rekening Terdaftar</option>
                            <option value="Tanpa Rekening">Belum Ada Rekening (0)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200/80">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table id="kelompok-belanja-table" class="min-w-full divide-y divide-gray-200 text-left text-sm">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-3.5 text-xs font-bold text-slate-600 uppercase tracking-wider">
                                        Kode</th>
                                    <th class="px-6 py-3.5 text-xs font-bold text-slate-600 uppercase tracking-wider">
                                        Nama Kelompok Belanja</th>
                                    <th class="px-6 py-3.5 text-xs font-bold text-slate-600 uppercase tracking-wider">
                                        Rekening Terdaftar</th>
                                    <th class="px-6 py-3.5 text-xs font-bold text-slate-600 uppercase tracking-wider">
                                        Status</th>
                                    <th class="px-6 py-3.5 text-right text-xs font-bold text-slate-600 uppercase tracking-wider">
                                        Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($groups as $group)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-black text-indigo-900 font-mono">
                                            {{ $group->kode }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">
                                            {{ $group->name }}
                                        </td>
                                        <td data-search="{{ $group->account_codes_count > 0 ? 'Ada Rekening' : 'Tanpa Rekening' }}" 
                                            data-filter="{{ $group->account_codes_count > 0 ? 'Ada Rekening' : 'Tanpa Rekening' }}" 
                                            data-order="{{ $group->account_codes_count }}"
                                            class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="px-2.5 py-1 inline-flex items-center gap-1.5 text-xs font-semibold rounded-full {{ $group->account_codes_count > 0 ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-slate-100 text-slate-500 border border-slate-200' }}">
                                                <span>📋</span>
                                                <span>{{ $group->account_codes_count }} Rekening</span>
                                            </span>
                                        </td>
                                        <td data-search="{{ $group->is_active ? 'Active' : 'Inactive' }}" 
                                            data-filter="{{ $group->is_active ? 'Active' : 'Inactive' }}" 
                                            class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-full {{ $group->is_active ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-gray-100 text-gray-600 border border-gray-200' }}">
                                                {{ $group->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                            <a href="{{ route('admin.kelompok-belanja.edit', $group) }}"
                                                class="text-indigo-600 hover:text-indigo-900 font-semibold transition-colors mr-2">Edit</a>
                                            <form action="{{ route('admin.kelompok-belanja.destroy', $group) }}" method="POST"
                                                class="inline-block"
                                                onsubmit="return confirm('Apakah Anda yakin ingin ' + ({{ $group->is_active ? 'true' : 'false' }} ? 'menonaktifkan' : 'mengaktifkan') + ' kelompok belanja {{ addslashes($group->name) }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="{{ $group->is_active ? 'text-amber-600 hover:text-amber-900 font-semibold' : 'text-green-600 hover:text-green-900 font-semibold' }} transition-colors">
                                                    {{ $group->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 whitespace-nowrap text-sm text-center text-gray-500">
                                            Tidak ada data kelompok belanja ditemukan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://cdn.datatables.net/2.0.3/css/dataTables.tailwindcss.css">
        <style>
            /* Custom minimalist datatables adjustment */
            div.dt-container div.dt-layout-row {
                margin-bottom: 0.75rem;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.datatables.net/2.0.3/js/dataTables.js"></script>
        <script src="https://cdn.datatables.net/2.0.3/js/dataTables.tailwindcss.js"></script>
        <script>
            $(document).ready(function() {
                const escapeRegex = (string) => string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

                const table = $('#kelompok-belanja-table').DataTable({
                    responsive: true,
                    columnDefs: [
                        { targets: [4], orderable: false, searchable: false }
                    ],
                    language: {
                        search: "Cari Bebas:",
                        searchPlaceholder: "Kode atau Nama Kelompok...",
                        lengthMenu: "Tampilkan _MENU_ data",
                        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ kelompok belanja",
                        infoEmpty: "Menampilkan 0 sampai 0 dari 0 kelompok belanja",
                        infoFiltered: "(disaring dari _MAX_ total kelompok)",
                        paginate: {
                            first: "Pertama",
                            last: "Terakhir",
                            next: "Selanjutnya",
                            previous: "Sebelumnya"
                        }
                    }
                });

                // Filter 1: Status Kelompok Belanja (Column Index 3)
                $('#filter-status').on('change', function() {
                    const val = $(this).val();
                    table.column(3).search(val ? '^\\s*' + escapeRegex(val) + '\\s*$' : '', true, false).draw();
                });

                // Filter 2: Keterikatan Rekening (Column Index 2)
                $('#filter-accounts').on('change', function() {
                    const val = $(this).val();
                    table.column(2).search(val ? '^\\s*' + escapeRegex(val) + '\\s*$' : '', true, false).draw();
                });

                // Tombol Reset Semua Filter
                $('#btn-reset-filters').on('click', function() {
                    $('#filter-status').val('');
                    $('#filter-accounts').val('');

                    table.columns().search('').draw();
                    table.search('').draw();
                });
            });
        </script>
    @endpush
</x-app-layout>