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

            <!-- Info Permendagri 108 -->
            <div class="bg-blue-50/70 border border-blue-200 rounded-2xl p-4 text-xs text-blue-800 flex items-start gap-3">
                <span class="text-xl">ℹ️</span>
                <div class="leading-relaxed">
                    <strong>Pedoman Kodefikasi BMD:</strong> Seluruh master barang mengacu pada Peraturan Menteri Dalam Negeri Nomor 108 Tahun 2016 tentang Penggolongan dan Kodefikasi Barang Milik Daerah. Data ini menjadi referensi baku dalam permohonan Rencana Kebutuhan Barang Milik Daerah (RKBMD) antar-operator.
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
                                        Nama Barang</th>
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
                $('#master-barangs-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('admin.master-barangs.index') }}",
                        type: 'GET'
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
                        zeroRecords: "Tidak ada data barang yang sesuai dengan pencarian",
                        search: "Cari Barang:",
                        searchPlaceholder: "Kode, nama barang, satuan...",
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
            });
        </script>
    @endpush
</x-app-layout>
