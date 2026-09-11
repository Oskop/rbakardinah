<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Manajemen Sub-Unit Kerja') }}
                </h2>
                <p class="text-xs text-slate-500 mt-1">Satuan Kerja Operasional Eselon IV & Non-Eselon (Sub-Bagian, Seksi, Instalasi, Komite, Tim)</p>
            </div>
            <a href="{{ route('admin.sub-units.create') }}"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
                <span>➕</span> <span class="ml-1.5">Tambah Sub-Unit</span>
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
                            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-800">Filter Sub-Unit Kerja</h3>
                            <p class="text-[11px] text-slate-400">Saring satuan kerja operasional berdasarkan unit induk, tipe, atau status</p>
                        </div>
                    </div>
                    <button type="button" id="btn-reset-filters"
                        class="text-xs text-indigo-600 hover:text-indigo-800 font-bold hover:underline inline-flex items-center gap-1.5 transition-colors">
                        <span>🔄</span>
                        <span>Reset Semua Filter</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Filter 1: Unit Induk -->
                    <div>
                        <label for="filter-unit" class="block text-xs font-bold text-slate-700 mb-1.5">Unit Kerja Induk (Eselon III)</label>
                        <select id="filter-unit" class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                            <option value="">Semua Unit Induk</option>
                            @foreach($units as $u)
                                <option value="{{ $u->name }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter 2: Tipe / Kategori -->
                    <div>
                        <label for="filter-type" class="block text-xs font-bold text-slate-700 mb-1.5">Tipe Satuan Kerja</label>
                        <select id="filter-type" class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                            <option value="">Semua Tipe</option>
                            @foreach($types as $t)
                                <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter 3: Status -->
                    <div>
                        <label for="filter-status" class="block text-xs font-bold text-slate-700 mb-1.5">Status Aktif</label>
                        <select id="filter-status" class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                            <option value="">Semua Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200/80">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table id="sub-units-table" class="min-w-full divide-y divide-gray-200 text-left text-sm">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-3.5 text-xs font-bold text-slate-600 uppercase tracking-wider">
                                        Kode</th>
                                    <th class="px-6 py-3.5 text-xs font-bold text-slate-600 uppercase tracking-wider">
                                        Nama Sub-Unit</th>
                                    <th class="px-6 py-3.5 text-xs font-bold text-slate-600 uppercase tracking-wider">
                                        Unit Induk (Eselon III)</th>
                                    <th class="px-6 py-3.5 text-xs font-bold text-slate-600 uppercase tracking-wider">
                                        Tipe</th>
                                    <th class="px-6 py-3.5 text-xs font-bold text-slate-600 uppercase tracking-wider">
                                        Pegawai Terdaftar</th>
                                    <th class="px-6 py-3.5 text-xs font-bold text-slate-600 uppercase tracking-wider">
                                        Status</th>
                                    <th class="px-6 py-3.5 text-right text-xs font-bold text-slate-600 uppercase tracking-wider">
                                        Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($subUnits as $sub)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-xs font-black text-indigo-900 font-mono">
                                            {{ $sub->code ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold">
                                            {{ $sub->name }}
                                        </td>
                                        <td data-search="{{ $sub->unit ? $sub->unit->name : '-' }}" data-filter="{{ $sub->unit ? $sub->unit->name : '-' }}" class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($sub->unit)
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-800 border border-slate-200">
                                                    🏢 {{ $sub->unit->name }}
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-400 italic">Tanpa Unit Induk</span>
                                            @endif
                                        </td>
                                        <td data-search="{{ $sub->type }}" data-filter="{{ $sub->type }}" class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                {{ $sub->type === 'Sub Bagian' || $sub->type === 'Sub Bidang' ? 'bg-amber-100 text-amber-800 border border-amber-200' :
                                                    ($sub->type === 'Instalasi' ? 'bg-blue-100 text-blue-800 border border-blue-200' :
                                                    ($sub->type === 'Komite' ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200')) }}">
                                                {{ $sub->type ?? 'Unit' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $sub->users_count > 0 ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-gray-100 text-gray-500' }}">
                                                👥 {{ $sub->users_count }} Pegawai
                                            </span>
                                        </td>
                                        <td data-search="{{ $sub->is_active ? 'Active' : 'Inactive' }}" data-filter="{{ $sub->is_active ? 'Active' : 'Inactive' }}" class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($sub->is_active)
                                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                    Active
                                                </span>
                                            @else
                                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-gray-100 text-gray-600 border border-gray-200">
                                                    Inactive
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('admin.sub-units.edit', $sub) }}"
                                                    class="inline-flex items-center px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold transition-colors">
                                                    ✏️ Edit
                                                </a>
                                                <form action="{{ route('admin.sub-units.destroy', $sub) }}" method="POST" class="inline"
                                                    onsubmit="return confirm('Apakah Anda yakin ingin {{ $sub->is_active ? 'menonaktifkan' : 'mengaktifkan' }} sub-unit ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-bold transition-colors {{ $sub->is_active ? 'bg-amber-50 hover:bg-amber-100 text-amber-700' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700' }}">
                                                        {{ $sub->is_active ? 'Deactivate' : 'Activate' }}
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500 italic">
                                            Belum ada sub-unit kerja terdaftar.
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

    @push('scripts')
    <script>
        $(document).ready(function() {
            var table = $('#sub-units-table').DataTable({
                responsive: true,
                language: {
                    search: "Cari Sub-Unit:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ sub-unit",
                    infoEmpty: "Menampilkan 0 data",
                    infoFiltered: "(disaring dari _MAX_ total sub-unit)",
                    zeroRecords: "Tidak ada sub-unit yang cocok dengan pencarian",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "Selanjutnya",
                        previous: "Sebelumnya"
                    }
                },
                columnDefs: [
                    { orderable: false, targets: [6] }
                ]
            });

            $('#filter-unit').on('change', function() {
                table.column(2).search(this.value ? '^' + this.value + '$' : '', true, false).draw();
            });

            $('#filter-type').on('change', function() {
                table.column(3).search(this.value ? '^' + this.value + '$' : '', true, false).draw();
            });

            $('#filter-status').on('change', function() {
                table.column(5).search(this.value ? '^' + this.value + '$' : '', true, false).draw();
            });

            $('#btn-reset-filters').on('click', function() {
                $('#filter-unit').val('');
                $('#filter-type').val('');
                $('#filter-status').val('');
                table.columns().search('').draw();
            });
        });
    </script>
    @endpush
</x-app-layout>
