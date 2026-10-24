<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
                <span>📦</span>
                <span>{{ __('Master Barang BMD (Permendagri No. 108 Tahun 2016)') }}</span>
            </h2>
            <a href="{{ route('admin.master-barangs.create') }}"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
                <span>➕</span> <span class="ml-1.5">Tambah Barang BMD</span>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-100 mx-auto sm:px-6 lg:px-8 space-y-6">
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

            <!-- Info Permendagri 108 -->
            <div class="bg-blue-50/70 border border-blue-200 rounded-2xl p-4 text-xs text-blue-800 flex items-start gap-3">
                <span class="text-xl">ℹ️</span>
                <div class="leading-relaxed">
                    <strong>Pedoman Kodefikasi BMD:</strong> Seluruh master barang mengacu pada Peraturan Menteri Dalam Negeri Nomor 108 Tahun 2016 tentang Penggolongan dan Kodefikasi Barang Milik Daerah. Data ini menjadi referensi baku dalam permohonan Rencana Kebutuhan Barang Milik Daerah (RKBMD) antar-operator.
                </div>
            </div>

            <!-- Filter Kategori Toolbar -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200/80">
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-sm">🗂️</span>
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Filter Hierarki Kategori Barang</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                    <!-- Filter Level 5: Rincian Objek -->
                    <div class="md:col-span-5">
                        <label for="filter-level5" class="block text-[11px] font-semibold text-slate-600 mb-1">
                            Kategori Induk (Level 5 - Rincian Objek)
                        </label>
                        <select id="filter-level5"
                            class="w-full text-xs rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2">
                            <option value="">-- Semua Kategori Level 5 ({{ count($level5List) }} Kategori) --</option>
                            @foreach ($level5List as $lvl5)
                                <option value="{{ $lvl5->kode_level5 }}">
                                    [{{ $lvl5->kode_level5 }}] {{ $lvl5->nama_level5 }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Level 6: Sub Rincian Objek -->
                    <div class="md:col-span-5">
                        <label for="filter-level6" class="block text-[11px] font-semibold text-slate-600 mb-1">
                            Sub-Kategori (Level 6 - Sub Rincian Objek)
                        </label>
                        <select id="filter-level6" disabled
                            class="w-full text-xs rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2 disabled:bg-slate-50 disabled:text-slate-400">
                            <option value="">-- Pilih Level 5 Terlebih Dahulu --</option>
                        </select>
                    </div>

                    <!-- Tombol Reset Filter -->
                    <div class="md:col-span-2">
                        <button type="button" id="btn-reset-filters"
                            class="w-full inline-flex items-center justify-center px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg border border-slate-300 transition-colors shadow-sm">
                            <span>🔄</span> <span class="ml-1.5">Reset Filter</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200/80">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table id="master-barangs-table" class="min-w-full divide-y divide-gray-200 stripe hover">
                            <thead class="bg-gray-50/80">
                                <tr>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Kode Barang BMD</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Nama Barang & Kategori</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Satuan</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Status</th>
                                    <th class="px-6 py-3.5 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <!-- Data dimuat secara dinamis oleh DataTables Server-Side Processing -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://cdn.datatables.net/2.0.3/css/dataTables.tailwindcss.css">
    @endpush

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.datatables.net/2.0.3/js/dataTables.js"></script>
        <script src="https://cdn.datatables.net/2.0.3/js/dataTables.tailwindcss.js"></script>
        <script>
            $(document).ready(function() {
                var table = $('#master-barangs-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('admin.master-barangs.index') }}",
                        type: 'GET',
                        data: function(d) {
                            d.level5 = $('#filter-level5').val();
                            d.level6 = $('#filter-level6').val();
                        }
                    },
                    responsive: true,
                    order: [[0, 'desc']],
                    columns: [
                        { data: 'kode_barang', name: 'kode_barang', className: 'px-6 py-4 whitespace-nowrap text-xs font-mono font-bold text-indigo-700' },
                        { data: 'nama_barang', name: 'nama_barang', className: 'px-6 py-4' },
                        { data: 'satuan', name: 'satuan', className: 'px-6 py-4 whitespace-nowrap text-xs text-gray-600 font-medium' },
                        { data: 'is_active', name: 'is_active', className: 'px-6 py-4 whitespace-nowrap text-xs' },
                        { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2' }
                    ],
                    language: {
                        processing: '<div class="flex items-center justify-center p-3 text-xs text-indigo-700 font-semibold"><svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>Memuat data katalog barang...</div>',
                        emptyTable: "Belum ada data master barang BMD.",
                        zeroRecords: "Tidak ada data barang yang sesuai dengan filter atau pencarian",
                        search: "Cari Cerdas (Smart Search):",
                        searchPlaceholder: "Contoh: laptop, whiteboard, printer, 1.3.2...",
                        lengthMenu: "Tampilkan _MENU_ data",
                        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ barang",
                        infoEmpty: "Menampilkan 0 sampai 0 dari 0 barang",
                        infoFiltered: "(disaring dari _MAX_ total data)",
                        paginate: {
                            first: "Pertama",
                            last: "Terakhir",
                            next: "Selanjutnya",
                            previous: "Sebelumnya"
                        }
                    },
                    columnDefs: [
                        { orderable: false, searchable: false, targets: [4] },
                        { defaultContent: "-", targets: "_all" }
                    ]
                });

                // Cascading filter: Ketika Level 5 dipilih
                $('#filter-level5').on('change', function() {
                    var selectedLvl5 = $(this).val();
                    var $lvl6Select = $('#filter-level6');

                    $lvl6Select.empty();

                    if (!selectedLvl5) {
                        $lvl6Select.append('<option value="">-- Pilih Level 5 Terlebih Dahulu --</option>');
                        $lvl6Select.prop('disabled', true);
                        table.ajax.reload();
                        return;
                    }

                    $lvl6Select.prop('disabled', true);
                    $lvl6Select.append('<option value="">Memuat Sub-Kategori...</option>');

                    $.ajax({
                        url: "{{ route('admin.master-barangs.level6') }}",
                        type: 'GET',
                        data: { level5: selectedLvl5 },
                        success: function(items) {
                            $lvl6Select.empty();
                            $lvl6Select.append('<option value="">-- Semua Sub-Kategori Level 6 (' + items.length + ') --</option>');
                            $.each(items, function(index, item) {
                                $lvl6Select.append('<option value="' + item.kode_level6 + '">[' + item.kode_level6 + '] ' + item.nama_level6 + '</option>');
                            });
                            $lvl6Select.prop('disabled', false);
                            table.ajax.reload();
                        },
                        error: function() {
                            $lvl6Select.empty();
                            $lvl6Select.append('<option value="">Gagal memuat sub-kategori</option>');
                            $lvl6Select.prop('disabled', false);
                        }
                    });
                });

                // Ketika Level 6 dipilih
                $('#filter-level6').on('change', function() {
                    table.ajax.reload();
                });

                // Tombol Reset Filter
                $('#btn-reset-filters').on('click', function() {
                    $('#filter-level5').val('');
                    var $lvl6 = $('#filter-level6');
                    $lvl6.empty().append('<option value="">-- Pilih Level 5 Terlebih Dahulu --</option>').prop('disabled', true);
                    table.search('').draw();
                    table.ajax.reload();
                });
            });
        </script>
    @endpush
</x-app-layout>
