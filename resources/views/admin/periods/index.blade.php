<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Manajemen Periode RBA') }}
            </h2>
            <a href="{{ route('admin.periods.create') }}"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
                <span>➕</span> <span class="ml-1.5">Tambah Periode</span>
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
                            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-800">Filter Kolom Periode RBA</h3>
                            <p class="text-[11px] text-slate-400">Saring periode penganggaran berdasarkan kriteria spesifik di bawah ini</p>
                        </div>
                    </div>
                    <button type="button" id="btn-reset-filters"
                        class="text-xs text-indigo-600 hover:text-indigo-800 font-bold hover:underline inline-flex items-center gap-1.5 transition-colors">
                        <span>🔄</span>
                        <span>Reset Semua Filter</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Filter 1: Status Periode -->
                    <div>
                        <label for="filter-status" class="block text-xs font-bold text-slate-700 mb-1.5">Status Periode</label>
                        <select id="filter-status" class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                            <option value="">Semua Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <!-- Filter 2: Keterikatan RBA Header -->
                    <div>
                        <label for="filter-headers" class="block text-xs font-bold text-slate-700 mb-1.5">Penggunaan pada Dokumen RBA</label>
                        <select id="filter-headers" class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                            <option value="">Semua Periode</option>
                            <option value="Used">Digunakan dalam RBA (≥ 1 Dokumen)</option>
                            <option value="Unused">Belum Digunakan (0 Dokumen)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200/80">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table id="periods-table" class="min-w-full divide-y divide-gray-200 text-left text-sm">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-3.5 text-xs font-bold text-slate-600 uppercase tracking-wider">
                                        ID</th>
                                    <th class="px-6 py-3.5 text-xs font-bold text-slate-600 uppercase tracking-wider">
                                        Nama Periode RBA</th>
                                    <th class="px-6 py-3.5 text-xs font-bold text-slate-600 uppercase tracking-wider">
                                        RBA Terdaftar</th>
                                    <th class="px-6 py-3.5 text-xs font-bold text-slate-600 uppercase tracking-wider">
                                        Status</th>
                                    <th class="px-6 py-3.5 text-right text-xs font-bold text-slate-600 uppercase tracking-wider">
                                        Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($periods as $period)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-500 font-mono">
                                            #{{ $period->id }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                            {{ $period->name }}
                                        </td>
                                        <td data-search="{{ ($period->headers_count ?? 0) > 0 ? 'Used' : 'Unused' }}" 
                                            data-filter="{{ ($period->headers_count ?? 0) > 0 ? 'Used' : 'Unused' }}"
                                            class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if(($period->headers_count ?? 0) > 0)
                                                <span class="px-2.5 py-1 inline-flex items-center text-xs font-semibold rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                    📊 {{ $period->headers_count }} Dokumen RBA
                                                </span>
                                            @else
                                                <span class="px-2.5 py-1 inline-flex items-center text-xs font-semibold rounded-full bg-slate-100 text-slate-500 border border-slate-200">
                                                    0 Dokumen RBA
                                                </span>
                                            @endif
                                        </td>
                                        <td data-search="{{ $period->is_active ? 'Active' : 'Inactive' }}" 
                                            data-filter="{{ $period->is_active ? 'Active' : 'Inactive' }}" 
                                            class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-full {{ $period->is_active ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-gray-100 text-gray-600 border border-gray-200' }}">
                                                {{ $period->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                            <a href="{{ route('admin.periods.edit', $period) }}"
                                                class="text-indigo-600 hover:text-indigo-900 font-semibold transition-colors mr-2">Edit</a>
                                            <form action="{{ route('admin.periods.destroy', $period) }}" method="POST"
                                                class="inline-block"
                                                onsubmit="return confirm('Apakah Anda yakin ingin ' + ({{ $period->is_active ? 'true' : 'false' }} ? 'menonaktifkan' : 'mengaktifkan') + ' periode {{ addslashes($period->name) }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="{{ $period->is_active ? 'text-amber-600 hover:text-amber-900 font-semibold' : 'text-green-600 hover:text-green-900 font-semibold' }} transition-colors">
                                                    {{ $period->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 whitespace-nowrap text-sm text-center text-gray-500">
                                            Tidak ada data periode ditemukan.
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

                const table = $('#periods-table').DataTable({
                    responsive: true,
                    columnDefs: [
                        { targets: [4], orderable: false, searchable: false }
                    ],
                    language: {
                        search: "Cari Bebas:",
                        searchPlaceholder: "Nama Periode...",
                        lengthMenu: "Tampilkan _MENU_ data",
                        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ periode",
                        infoEmpty: "Menampilkan 0 sampai 0 dari 0 periode",
                        infoFiltered: "(disaring dari _MAX_ total periode)",
                        paginate: {
                            first: "Pertama",
                            last: "Terakhir",
                            next: "Selanjutnya",
                            previous: "Sebelumnya"
                        }
                    }
                });

                // Filter 1: Status Periode (Column Index 3)
                $('#filter-status').on('change', function() {
                    const val = $(this).val();
                    table.column(3).search(val ? '^\\s*' + escapeRegex(val) + '\\s*$' : '', true, false).draw();
                });

                // Filter 2: Keterikatan RBA Header (Column Index 2)
                $('#filter-headers').on('change', function() {
                    const val = $(this).val();
                    table.column(2).search(val ? '^\\s*' + escapeRegex(val) + '\\s*$' : '', true, false).draw();
                });

                // Tombol Reset Semua Filter
                $('#btn-reset-filters').on('click', function() {
                    $('#filter-status').val('');
                    $('#filter-headers').val('');

                    table.columns().search('').draw();
                    table.search('').draw();
                });
            });
        </script>
    @endpush
</x-app-layout>