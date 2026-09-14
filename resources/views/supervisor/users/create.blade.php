<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create New User for ') }} {{ Auth::user()->unit?->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('supervisor.users.store') }}" class="space-y-6">
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

                        <!-- Role (Locked to Operator) -->
                        <div>
                            <x-input-label for="role_display" :value="__('Role')" />
                            <x-text-input id="role_display" class="block mt-1 w-full bg-gray-100" type="text"
                                value="Operator" readonly />
                            <p class="mt-1 text-xs text-gray-500">Supervisor hanya dapat membuat user dengan role
                                Operator.</p>
                        </div>

                        <!-- Unit (Locked to Supervisor's Unit) -->
                        <div>
                            <x-input-label for="unit_display" :value="__('Unit Induk (Eselon III)')" />
                            <x-text-input id="unit_display" class="block mt-1 w-full bg-gray-100" type="text"
                                value="{{ Auth::user()->unit?->name }}" readonly />
                        </div>

                        <!-- Sub-Unit -->
                        <div>
                            <x-input-label for="sub_unit_id" :value="__('Sub-Unit Kerja (Eselon IV / Non-Eselon)')" />
                            <select id="sub_unit_id" name="sub_unit_id"
                                class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">None / Langsung di bawah Unit Induk</option>
                                @foreach($subUnits as $sub)
                                    <option value="{{ $sub->id }}" {{ old('sub_unit_id') == $sub->id ? 'selected' : '' }}>
                                        📌 {{ $sub->name }} ({{ $sub->type }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Pilih satuan kerja operasional tempat operator ditugaskan.</p>
                            <x-input-error :messages="$errors->get('sub_unit_id')" class="mt-2" />
                        </div>

                        <!-- Jabatan -->
                        <div>
                            <x-input-label for="jabatan" :value="__('Jabatan Organisasi / Kedinasan')" />
                            <x-text-input id="jabatan" class="block mt-1 w-full" type="text" name="jabatan"
                                :value="old('jabatan')" placeholder="Contoh: Pranata Komputer, Staf Operasional, Apoteker" />
                            <x-input-error :messages="$errors->get('jabatan')" class="mt-2" />
                        </div>

                        <!-- Hak Pengusulan RBA -->
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl">
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
                                Centang opsi ini jika operator berwenang menginput rincian belanja usulan, mengisi latar belakang, dan mengajukan usulan ke Supervisor. Jika <strong>tidak dicentang</strong>, akun ini berstatus sebagai <strong>Viewer / Peninjau</strong> (hanya dapat melihat data usulan & mencetak laporan sub-unitnya).
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
                            <a href="{{ route('supervisor.users.index') }}"
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
</x-app-layout>