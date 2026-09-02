<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('User Management') }}
            </h2>
            <a href="{{ route('admin.users.create') }}"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Add New User
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded shadow-sm">
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
                            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-800">Filter Kolom Pengguna</h3>
                            <p class="text-[11px] text-slate-400">Saring pengguna berdasarkan kriteria spesifik di bawah ini</p>
                        </div>
                    </div>
                    <button type="button" id="btn-reset-filters"
                        class="text-xs text-indigo-600 hover:text-indigo-800 font-bold hover:underline inline-flex items-center gap-1.5 transition-colors">
                        <span>🔄</span>
                        <span>Reset Semua Filter</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                    <!-- Filter 1: Role -->
                    <div>
                        <label for="filter-role" class="block text-xs font-bold text-slate-700 mb-1.5">Peran / Role</label>
                        <select id="filter-role" class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                            <option value="">Semua Role</option>
                            <option value="Administrator">Administrator</option>
                            <option value="Supervisor">Supervisor</option>
                            <option value="Operator">Operator</option>
                        </select>
                    </div>

                    <!-- Filter 2: Unit Kerja -->
                    <div>
                        <label for="filter-unit" class="block text-xs font-bold text-slate-700 mb-1.5">Unit Kerja</label>
                        <select id="filter-unit" class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                            <option value="">Semua Unit Kerja</option>
                            <option value="Belum Ditugaskan">⚠️ Belum Ditugaskan / Tanpa Unit</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->name }}">{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter 3: Status Akun -->
                    <div>
                        <label for="filter-status" class="block text-xs font-bold text-slate-700 mb-1.5">Status Akun</label>
                        <select id="filter-status" class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                            <option value="">Semua Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <!-- Filter 4: Tipe Akun / Provider -->
                    <div>
                        <label for="filter-provider" class="block text-xs font-bold text-slate-700 mb-1.5">Tipe Akun (Metode Login)</label>
                        <select id="filter-provider" class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                            <option value="">Semua Tipe Akun</option>
                            <option value="SSO SIMRS">🏥 SSO SIMRS</option>
                            <option value="Akun Lokal">🔐 Akun Lokal</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Users Table Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200/80">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table id="users-table" class="min-w-full divide-y divide-gray-200 stripe hover">
                            <thead class="bg-gray-50/80">
                                <tr>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Nama / Pegawai</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Email</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Role</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Unit Kerja</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Status</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Tipe Akun</th>
                                    <th class="px-6 py-3.5 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">
                                        Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($users as $user)
                                    <tr>
                                        <!-- Column 0: Nama & NIP -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-semibold text-gray-900">{{ $user->name }}</div>
                                            @if($user->nip)
                                                <div class="text-[11px] text-gray-500 font-mono flex items-center gap-1 mt-0.5">
                                                    <span>🪪</span>
                                                    <span>{{ $user->nip }}</span>
                                                </div>
                                            @endif
                                        </td>

                                        <!-- Column 1: Email -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $user->email }}
                                        </td>

                                        <!-- Column 2: Role -->
                                        <td data-search="{{ $user->role }}" data-filter="{{ $user->role }}" class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-full 
                                                {{ $user->role === 'Administrator' ? 'bg-red-100 text-red-800 border border-red-200' :
                                                    ($user->role === 'Supervisor' ? 'bg-blue-100 text-blue-800 border border-blue-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200') }}">{{ $user->role }}</span>
                                        </td>

                                        <!-- Column 3: Unit Kerja -->
                                        <td data-search="{{ $user->unit ? $user->unit->name : 'Belum Ditugaskan' }}" data-filter="{{ $user->unit ? $user->unit->name : 'Belum Ditugaskan' }}" class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($user->unit)
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 text-slate-800 border border-slate-200">{{ $user->unit->name }}</span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">⚠️ Belum Ditugaskan</span>
                                            @endif
                                        </td>

                                        <!-- Column 4: Status -->
                                        <td data-search="{{ $user->is_active ? 'Active' : 'Inactive' }}" data-filter="{{ $user->is_active ? 'Active' : 'Inactive' }}" class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-full 
                                                {{ $user->is_active ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-gray-100 text-gray-600 border border-gray-200' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
                                        </td>

                                        <!-- Column 5: Tipe Akun -->
                                        <td data-search="{{ $user->auth_provider === 'simrs_oidc' ? 'SSO SIMRS' : 'Akun Lokal' }}" data-filter="{{ $user->auth_provider === 'simrs_oidc' ? 'SSO SIMRS' : 'Akun Lokal' }}" class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($user->auth_provider === 'simrs_oidc')
                                                <span class="px-2.5 py-1 inline-flex items-center gap-1 text-[11px] leading-4 font-bold rounded-full bg-purple-100 text-purple-800 border border-purple-200">
                                                    <span>🏥</span>
                                                    <span>SSO SIMRS</span>
                                                </span>
                                            @else
                                                <span class="px-2.5 py-1 inline-flex items-center gap-1 text-[11px] leading-4 font-medium rounded-full bg-slate-100 text-slate-700 border border-slate-200">
                                                    <span>🔐</span>
                                                    <span>Akun Lokal</span>
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Column 6: Aksi -->
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                            <a href="{{ route('admin.users.edit', $user) }}"
                                                class="text-indigo-600 hover:text-indigo-900 font-semibold transition-colors">Edit</a>
                                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                                class="inline-block"
                                                onsubmit="return confirm('Apakah Anda yakin ingin ' + ({{ $user->is_active ? 'true' : 'false' }} ? 'menonaktifkan' : 'mengaktifkan') + ' user ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="{{ $user->is_active ? 'text-red-600 hover:text-red-900 font-semibold' : 'text-green-600 hover:text-green-900 font-semibold' }} @if($user->id === auth()->id()) opacity-50 cursor-not-allowed @endif"
                                                    @if($user->id === auth()->id()) disabled @endif>
                                                    {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-8 whitespace-nowrap text-sm text-center text-gray-500">
                                            Tidak ada data pengguna ditemukan.
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

                const table = $('#users-table').DataTable({
                    responsive: true,
                    language: {
                        search: "Cari Bebas:",
                        searchPlaceholder: "Nama, NIP, atau Email...",
                        lengthMenu: "Tampilkan _MENU_ data",
                        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ pengguna",
                        infoEmpty: "Menampilkan 0 sampai 0 dari 0 pengguna",
                        infoFiltered: "(disaring dari _MAX_ total pengguna)",
                        paginate: {
                            first: "Pertama",
                            last: "Terakhir",
                            next: "Selanjutnya",
                            previous: "Sebelumnya"
                        }
                    }
                });

                // Filter 1: Peran / Role (Column Index 2)
                $('#filter-role').on('change', function() {
                    const val = $(this).val();
                    table.column(2).search(val ? '^\\s*' + escapeRegex(val) + '\\s*$' : '', true, false).draw();
                });

                // Filter 2: Unit Kerja (Column Index 3)
                $('#filter-unit').on('change', function() {
                    const val = $(this).val();
                    table.column(3).search(val ? escapeRegex(val) : '', true, false).draw();
                });

                // Filter 3: Status Akun (Column Index 4)
                $('#filter-status').on('change', function() {
                    const val = $(this).val();
                    table.column(4).search(val ? '^\\s*' + escapeRegex(val) + '\\s*$' : '', true, false).draw();
                });

                // Filter 4: Tipe Akun (Column Index 5)
                $('#filter-provider').on('change', function() {
                    const val = $(this).val();
                    table.column(5).search(val ? escapeRegex(val) : '', true, false).draw();
                });

                // Tombol Reset Semua Filter
                $('#btn-reset-filters').on('click', function() {
                    $('#filter-role').val('');
                    $('#filter-unit').val('');
                    $('#filter-status').val('');
                    $('#filter-provider').val('');

                    table.columns().search('').draw();
                    table.search('').draw();
                });
            });
        </script>
    @endpush
</x-app-layout>