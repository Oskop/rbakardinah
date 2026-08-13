<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Inisialisasi RBA Baru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="mb-6 text-sm text-gray-600">
                        Inisialisasi RBA baru akan otomatis membuat entri pengajuan untuk <strong>semua
                            unit yang aktif</strong>.
                    </p>

                    <form action="{{ route('admin.headers.store') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label for="year" class="block text-sm font-medium text-gray-700">Tahun Anggaran</label>
                            <input type="number" name="year" id="year" value="{{ old('year', date('Y')) }}" min="2000"
                                max="2100"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                required>
                            @error('year')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="period_id" class="block text-sm font-medium text-gray-700">Tahap/Fase RBA</label>
                            <select name="period_id" id="period_id"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                required>
                                <option value="">-- Pilih Tahap/Fase --</option>
                                @foreach($periods as $period)
                                    <option value="{{ $period->id }}" {{ old('period_id') == $period->id ? 'selected' : '' }}>
                                        {{ $period->name }} ({{ $period->year }})</option>
                                @endforeach
                            </select>
                            @error('period_id')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('admin.headers.index') }}"
                                class="text-sm text-gray-600 hover:text-gray-900 mr-4">Batal</a>
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Inisialisasi RBA
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>