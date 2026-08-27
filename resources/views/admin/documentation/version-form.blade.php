<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-bold text-xl text-gray-900 leading-tight">
                    {{ $isEdit ? __('Edit Versi Dokumentasi') : __('Tambah Versi Dokumentasi Baru') }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    {{ $isEdit ? "Memperbarui metadata versi {$version->version}" : "Mendaftarkan rilis versi dokumentasi web atau PDF baru" }}
                </p>
            </div>
            <a href="{{ route('admin.documentation.index') }}"
                class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-semibold border border-gray-300 transition">
                ← Kembali ke Daftar Versi
            </a>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ docType: '{{ old('type', $version->type ?? 'html') }}' }">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden p-6 sm:p-8">

                <form action="{{ $isEdit ? route('admin.documentation.versions.update', $version) : route('admin.documentation.versions.store') }}"
                    method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @if($isEdit)
                        @method('PUT')
                    @endif

                    <!-- Tipe Dokumentasi (Hanya saat create) -->
                    @if(!$isEdit)
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Tipe Dokumentasi <span class="text-rose-500">*</span></label>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="flex items-center p-4 rounded-xl border cursor-pointer transition"
                                    :class="docType === 'html' ? 'border-indigo-600 bg-indigo-50/50 shadow-sm' : 'border-gray-200 hover:bg-gray-50'">
                                    <input type="radio" name="type" value="html" x-model="docType" class="text-indigo-600 focus:ring-indigo-500">
                                    <div class="ml-3">
                                        <div class="font-bold text-xs text-gray-900 flex items-center gap-1">
                                            <span>📘</span> Panduan Web (HTML GoFiber Docs)
                                        </div>
                                        <p class="text-[11px] text-gray-500 mt-0.5">Struktur bab artikel interaktif</p>
                                    </div>
                                </label>
                                <label class="flex items-center p-4 rounded-xl border cursor-pointer transition"
                                    :class="docType === 'pdf' ? 'border-indigo-600 bg-indigo-50/50 shadow-sm' : 'border-gray-200 hover:bg-gray-50'">
                                    <input type="radio" name="type" value="pdf" x-model="docType" class="text-indigo-600 focus:ring-indigo-500">
                                    <div class="ml-3">
                                        <div class="font-bold text-xs text-gray-900 flex items-center gap-1">
                                            <span>📄</span> Manual Book PDF
                                        </div>
                                        <p class="text-[11px] text-gray-500 mt-0.5">Berkas cetak unduhan PDF resmi</p>
                                    </div>
                                </label>
                            </div>
                            <x-input-error class="mt-1" :messages="$errors->get('type')" />
                        </div>
                    @else
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between text-xs">
                            <span class="text-gray-600">Tipe Dokumen: <strong class="text-gray-900 uppercase">{{ $version->type }}</strong></span>
                            <span class="text-gray-400">Tipe tidak dapat diubah setelah rilis dibuat</span>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <!-- Nomor Versi -->
                        <div>
                            <x-input-label for="version" :value="__('Nomor Versi (misal: v1.1.0)')" class="text-xs font-bold text-gray-700 uppercase" />
                            <x-text-input id="version" name="version" type="text" class="mt-1 block w-full text-xs font-bold"
                                :value="old('version', $version->version)" placeholder="v1.0.0" required />
                            <x-input-error class="mt-1" :messages="$errors->get('version')" />
                        </div>

                        <!-- Tanggal Rilis -->
                        <div>
                            <x-input-label for="released_at" :value="__('Tanggal Rilis')" class="text-xs font-bold text-gray-700 uppercase" />
                            <x-text-input id="released_at" name="released_at" type="date" class="mt-1 block w-full text-xs"
                                :value="old('released_at', $version->released_at ? $version->released_at->format('Y-m-d') : date('Y-m-d'))" required />
                            <x-input-error class="mt-1" :messages="$errors->get('released_at')" />
                        </div>
                    </div>

                    <!-- Judul Dokumen -->
                    <div>
                        <x-input-label for="title" :value="__('Judul Dokumen / Rilis')" class="text-xs font-bold text-gray-700 uppercase" />
                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full text-xs"
                            :value="old('title', $version->title ?? 'Buku Panduan Penggunaan Sistem RBA RSUD Kardinah')" required />
                        <x-input-error class="mt-1" :messages="$errors->get('title')" />
                    </div>

                    <!-- File PDF Upload (Jika tipe PDF) -->
                    <div x-show="docType === 'pdf'" class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                        <x-input-label for="pdf_file" :value="__('Upload Berkas PDF Manual Book')" class="text-xs font-bold text-gray-700 uppercase" />
                        <input id="pdf_file" name="pdf_file" type="file" accept="application/pdf"
                            class="mt-2 block w-full text-xs text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-white focus:outline-none p-2" />
                        <p class="text-[11px] text-gray-400 mt-1">Format berkas harus .pdf (Maksimal 20MB).</p>
                        @if($isEdit && $version->file_path)
                            <div class="mt-2 text-xs text-gray-600 flex items-center gap-2">
                                <span>Berkas saat ini: <strong class="text-indigo-600">{{ basename($version->file_path) }}</strong> ({{ $version->formatted_file_size }})</span>
                            </div>
                        @endif
                        <x-input-error class="mt-1" :messages="$errors->get('pdf_file')" />
                    </div>

                    <!-- Clone Articles Option (Jika Create tipe HTML) -->
                    @if(!$isEdit)
                        <div x-show="docType === 'html'" class="p-4 bg-indigo-50/50 rounded-xl border border-indigo-100">
                            <label class="flex items-center gap-2.5 cursor-pointer">
                                <input type="checkbox" name="clone_from_active" value="1" checked
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <div class="text-xs font-bold text-indigo-900">Salin Otomatis Seluruh Artikel dari Versi Aktif</div>
                                    <p class="text-[11px] text-indigo-700 mt-0.5 leading-snug">
                                        Menduplikasi semua bab/artikel yang ada saat ini ke dalam versi baru ini sehingga Anda cukup mengubah poin-poin yang diperbarui.
                                    </p>
                                </div>
                            </label>
                        </div>
                    @endif

                    <!-- Catatan Pembaruan (Release Notes / Changelog) -->
                    <div>
                        <x-input-label for="release_notes" :value="__('Catatan Pembaruan / Ringkasan Rilis (Release Notes)')" class="text-xs font-bold text-gray-700 uppercase" />
                        <textarea id="release_notes" name="release_notes" rows="3"
                            placeholder="Tuliskan ringkasan fitur baru atau perbaikan pada versi ini..."
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs">{{ old('release_notes', $version->release_notes) }}</textarea>
                        <x-input-error class="mt-1" :messages="$errors->get('release_notes')" />
                    </div>

                    <!-- Jadikan Versi Aktif -->
                    <div class="pt-2">
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $version->is_active) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-xs font-bold text-gray-800">Jadikan rilis ini sebagai Versi Aktif (Live untuk seluruh pengguna)</span>
                        </label>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-100">
                        <a href="{{ route('admin.documentation.index') }}"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition">
                            Batal
                        </a>
                        <button type="submit"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md transition">
                            {{ $isEdit ? __('💾 Simpan Perubahan Versi') : __('➕ Simpan & Buat Versi') }}
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
