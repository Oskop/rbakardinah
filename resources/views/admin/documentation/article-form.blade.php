<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-bold text-xl text-gray-900 leading-tight">
                    {{ $isEdit ? __('Edit Artikel Panduan') : __('Tambah Artikel Panduan Baru') }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    Versi: <strong class="text-indigo-600">{{ $version->version }}</strong> ({{ $version->title }})
                </p>
            </div>
            <a href="{{ route('admin.documentation.articles.index', $version) }}"
                class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-semibold border border-gray-300 transition">
                ← Kembali ke Daftar Artikel
            </a>
        </div>
    </x-slot>

    <div class="py-8" x-data="articleEditor({{ Js::from(old('content', $article->content ?? '')) }})">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden p-6 sm:p-8">

                <form action="{{ $isEdit ? route('admin.documentation.articles.update', $article) : route('admin.documentation.articles.store', $version) }}"
                    method="POST" class="space-y-6">
                    @csrf
                    @if($isEdit)
                        @method('PUT')
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <!-- Kategori Bab -->
                        <div class="sm:col-span-2">
                            <x-input-label for="category" :value="__('Kategori Bab')" class="text-xs font-bold text-gray-700 uppercase" />
                            <input list="category-list" id="category" name="category" type="text"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs font-semibold"
                                value="{{ old('category', $article->category ?? '📝 Panduan Operator') }}" required
                                placeholder="Contoh: 📝 Panduan Operator" />
                            <datalist id="category-list">
                                @foreach($existingCategories as $cat)
                                    <option value="{{ $cat }}">
                                @endforeach
                                <option value="🚀 Pengenalan">
                                <option value="📝 Panduan Operator">
                                <option value="🔍 Panduan Supervisor">
                                <option value="👑 Panduan Administrator">
                                <option value="💡 FAQ & Bantuan">
                            </datalist>
                            <x-input-error class="mt-1" :messages="$errors->get('category')" />
                        </div>

                        <!-- Urutan Tampil -->
                        <div>
                            <x-input-label for="order" :value="__('Urutan Tampil')" class="text-xs font-bold text-gray-700 uppercase" />
                            <x-text-input id="order" name="order" type="number" min="0" class="mt-1 block w-full text-xs font-bold"
                                :value="old('order', $article->order ?? 1)" required />
                            <x-input-error class="mt-1" :messages="$errors->get('order')" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-6">
                        <!-- Ikon Topik -->
                        <div>
                            <x-input-label for="icon" :value="__('Ikon / Emoji')" class="text-xs font-bold text-gray-700 uppercase" />
                            <x-text-input id="icon" name="icon" type="text" class="mt-1 block w-full text-xs text-center"
                                :value="old('icon', $article->icon ?? '📄')" placeholder="📄" />
                            <x-input-error class="mt-1" :messages="$errors->get('icon')" />
                        </div>

                        <!-- Judul Artikel -->
                        <div class="sm:col-span-3">
                            <x-input-label for="title" :value="__('Judul Artikel / Bab')" class="text-xs font-bold text-gray-700 uppercase" />
                            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full text-xs font-bold"
                                :value="old('title', $article->title)" placeholder="Contoh: Penginputan Rincian Usulan Belanja" required
                                @input="if(!isEdit && !$refs.slugInput.value) { $refs.slugInput.value = $el.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '') }" />
                            <x-input-error class="mt-1" :messages="$errors->get('title')" />
                        </div>
                    </div>

                    <!-- Slug URL -->
                    <div>
                        <x-input-label for="slug" :value="__('Slug URL')" class="text-xs font-bold text-gray-700 uppercase" />
                        <x-text-input id="slug" name="slug" type="text" x-ref="slugInput" class="mt-1 block w-full text-xs font-mono"
                            :value="old('slug', $article->slug)" placeholder="penginputan-rincian-usulan-belanja" required />
                        <p class="text-[11px] text-gray-400 mt-1">Digunakan untuk URL bersih halaman dokumentasi.</p>
                        <x-input-error class="mt-1" :messages="$errors->get('slug')" />
                    </div>

                    <!-- Content Editor with Snippet Helpers -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <x-input-label for="content" :value="__('Konten Artikel Panduan (HTML / Rich Format)')" class="text-xs font-bold text-gray-700 uppercase" />
                            <!-- Quick Snippet Injectors -->
                            <div class="flex items-center gap-1.5 text-[11px]">
                                <span class="text-gray-400">Sisipkan Komponen:</span>
                                <button type="button" @click="insertSnippet('h2')" class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 rounded font-semibold text-gray-700">Sub-Judul (H2)</button>
                                <button type="button" @click="insertSnippet('tip')" class="px-2 py-0.5 bg-emerald-50 hover:bg-emerald-100 rounded font-semibold text-emerald-800">💡 Tip</button>
                                <button type="button" @click="insertSnippet('warning')" class="px-2 py-0.5 bg-amber-50 hover:bg-amber-100 rounded font-semibold text-amber-800">⚠️ Warning</button>
                                <button type="button" @click="insertSnippet('steps')" class="px-2 py-0.5 bg-indigo-50 hover:bg-indigo-100 rounded font-semibold text-indigo-800">🔢 Step Card</button>
                            </div>
                        </div>

                        <!-- Editor Textarea & Live Preview Tabs -->
                        <div class="border border-gray-300 rounded-xl overflow-hidden shadow-sm">
                            <div class="flex border-b border-gray-200 bg-slate-50 text-xs font-semibold">
                                <button type="button" @click="tab = 'editor'"
                                    :class="tab === 'editor' ? 'bg-white text-indigo-600 border-b-2 border-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-900'"
                                    class="px-4 py-2 transition">
                                    ✏️ Editor
                                </button>
                                <button type="button" @click="tab = 'preview'"
                                    :class="tab === 'preview' ? 'bg-white text-indigo-600 border-b-2 border-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-900'"
                                    class="px-4 py-2 transition">
                                    👁️ Live Preview
                                </button>
                            </div>

                            <div x-show="tab === 'editor'">
                                <textarea id="content" name="content" x-model="content" rows="16" required
                                    class="block w-full border-0 p-4 font-mono text-xs text-gray-900 focus:ring-0 focus:outline-none"
                                    placeholder="Tuliskan isi artikel menggunakan tag HTML (<h2>, <p>, <ul>, <div class='admonition'>, dll.)..."></textarea>
                            </div>

                            <div x-show="tab === 'preview'" class="p-6 bg-white min-h-[350px] docs-article leading-relaxed border-t border-gray-100 overflow-y-auto max-h-[500px]"
                                x-html="content">
                            </div>
                        </div>
                        <x-input-error class="mt-1" :messages="$errors->get('content')" />
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-100">
                        <a href="{{ route('admin.documentation.articles.index', $version) }}"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition">
                            Batal
                        </a>
                        <button type="submit"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md transition">
                            {{ $isEdit ? __('💾 Simpan Perubahan Artikel') : __('➕ Simpan Artikel Baru') }}
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function articleEditor(initialContent) {
            return {
                tab: 'editor',
                content: initialContent || '',
                isEdit: {{ $isEdit ? 'true' : 'false' }},

                insertSnippet(type) {
                    let snippet = '';
                    if (type === 'h2') {
                        snippet = '\n<h2>Judul Sub-Bab Baru</h2>\n<p>Isi penjelasan sub-bab di sini...</p>\n';
                    } else if (type === 'tip') {
                        snippet = '\n<div class="admonition admonition-tip">\n    <div class="admonition-title">💡 Tips Praktis</div>\n    <div class="admonition-content">\n        Tuliskan tips atau panduan cepat di sini.\n    </div>\n</div>\n';
                    } else if (type === 'warning') {
                        snippet = '\n<div class="admonition admonition-warning">\n    <div class="admonition-title">⚠️ Perhatian</div>\n    <div class="admonition-content">\n        Peringatan atau ketentuan penting yang wajib diperhatikan.\n    </div>\n</div>\n';
                    } else if (type === 'steps') {
                        snippet = '\n<div class="step-card">\n    <div class="step-badge">1</div>\n    <div class="step-content">\n        <h4>Judul Langkah Pertama</h4>\n        <p>Penjelasan detail langkah pertama yang harus dilakukan pengguna.</p>\n    </div>\n</div>\n';
                    }
                    this.content += snippet;
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
