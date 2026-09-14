<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create New User') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
                        @csrf

                        <!-- Name -->
                        <div>
                            <x-input-label for="name" :value="__('Name')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name"
                                :value="old('name')" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <!-- Email Address -->
                        <div>
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email"
                                :value="old('email')" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <!-- Role -->
                        <div>
                            <x-input-label for="role" :value="__('Role')" />
                            <select id="role" name="role"
                                class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                required>
                                <option value="" disabled selected>Select Role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role }}" {{ old('role') == $role ? 'selected' : '' }}>{{ $role }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-2" />
                        </div>

                        <!-- Unit -->
                        <div>
                            <x-input-label for="unit_id" :value="__('Unit Kerja Induk (Eselon III)')" />
                            <select id="unit_id" name="unit_id"
                                class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">None / General (Direksi & Sysadmin)</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                                        🏢 {{ $unit->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('unit_id')" class="mt-2" />
                        </div>

                        <!-- Sub-Unit -->
                        <div>
                            <x-input-label for="sub_unit_id" :value="__('Sub-Unit Kerja (Eselon IV / Non-Eselon)')" />
                            <select id="sub_unit_id" name="sub_unit_id"
                                class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">None / Langsung di bawah Unit Induk</option>
                                @foreach($subUnits as $sub)
                                    <option value="{{ $sub->id }}" data-unit="{{ $sub->unit_id }}" {{ old('sub_unit_id') == $sub->id ? 'selected' : '' }}>
                                        📌 {{ $sub->name }} ({{ $sub->type }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-slate-500 mt-1">Pilih satuan kerja operasional spesifik tempat pegawai bertugas.</p>
                            <x-input-error :messages="$errors->get('sub_unit_id')" class="mt-2" />
                        </div>

                        <!-- Jabatan -->
                        <div>
                            <x-input-label for="jabatan" :value="__('Jabatan Organisasi / Kedinasan')" />
                            <x-text-input id="jabatan" class="block mt-1 w-full" type="text" name="jabatan"
                                :value="old('jabatan')" placeholder="Contoh: Pranata Komputer Ahli Pertama, Staf Perencanaan, Apoteker" />
                            <x-input-error :messages="$errors->get('jabatan')" class="mt-2" />
                        </div>

                        <!-- Hak Pengusulan RBA (Khusus Operator) -->
                        <div id="can_propose_wrapper" class="p-4 bg-slate-50 border border-slate-200 rounded-xl">
                            <input type="hidden" name="can_propose" value="0">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="can_propose" id="can_propose" value="1"
                                    {{ old('can_propose', '1') == '1' ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 w-4 h-4">
                                <span class="ml-2.5 text-sm font-bold text-gray-800">
                                    ✍️ Hak Pengusulan RBA (Operator Pengusul / PIC)
                                </span>
                            </label>
                            <p class="text-xs text-slate-500 mt-1.5 ml-6.5 leading-relaxed">
                                Centang opsi ini jika pengguna berwenang menginput rincian belanja usulan, mengisi latar belakang, dan mengajukan berkas ke Supervisor. Jika <strong>tidak dicentang</strong>, akun ini berstatus sebagai <strong>Viewer / Peninjau</strong> (hanya dapat melihat data usulan & mencetak laporan sub-unitnya).
                            </p>
                        </div>

                        <!-- Password -->
                        <div>
                            <x-input-label for="password" :value="__('Password')" />
                            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password"
                                required autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password"
                                name="password_confirmation" required />
                            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('admin.users.index') }}"
                                class="text-sm text-gray-600 hover:text-gray-900 underline mr-4">
                                {{ __('Cancel') }}
                            </a>
                            <x-primary-button>
                                {{ __('Create User') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const roleSelect = document.getElementById('role');
            const canProposeWrapper = document.getElementById('can_propose_wrapper');
            const unitSelect = document.getElementById('unit_id');
            const subUnitSelect = document.getElementById('sub_unit_id');

            function toggleCanPropose() {
                if (!roleSelect || !canProposeWrapper) return;
                if (roleSelect.value === 'Operator') {
                    canProposeWrapper.style.display = 'block';
                } else {
                    canProposeWrapper.style.display = 'none';
                }
            }

            if (roleSelect) {
                roleSelect.addEventListener('change', toggleCanPropose);
                toggleCanPropose();
            }

            if (!unitSelect || !subUnitSelect) return;

            const originalSubOptions = Array.from(subUnitSelect.options);

            function filterSubUnits() {
                const selectedUnit = unitSelect.value;
                const currentSelectedSub = subUnitSelect.value;
                
                subUnitSelect.innerHTML = '';

                originalSubOptions.forEach(option => {
                    const unitAttr = option.getAttribute('data-unit');
                    if (!option.value || !selectedUnit || unitAttr === selectedUnit) {
                        subUnitSelect.appendChild(option.cloneNode(true));
                    }
                });

                const exists = Array.from(subUnitSelect.options).some(opt => opt.value === currentSelectedSub);
                if (exists) {
                    subUnitSelect.value = currentSelectedSub;
                }
            }

            unitSelect.addEventListener('change', filterSubUnits);
            filterSubUnits();
        });
    </script>
    @endpush
</x-app-layout>