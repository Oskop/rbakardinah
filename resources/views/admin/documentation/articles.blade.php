<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 bg-indigo-100 text-indigo-800 rounded-full text-xs font-bold">{{ $version->version }}</span>
                    <h2 class="font-bold text-xl text-gray-900 leading-tight">
                        {{ __('Daftar Bab & Artikel Panduan Web') }}
                    </h2>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    {{ $version->title }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.documentation.index') }}"
                    class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-semibold border border-gray-300 transition">
                    ← Kembali ke Versi
                </a>
                <a href="{{ route('admin.documentation.articles.create', $version) }}"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow transition">
                    <span>➕</span>
                    <span>Tambah Artikel Baru</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-lg text-emerald-800 text-sm font-medium flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @forelse($articles as $category => $items)
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="p-4 bg-slate-50 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">
                            {{ $category }} ({{ $items->count() }} Bab)
                        </h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead class="bg-white">
                                <tr>
                                    <th class="px-4 py-2.5 text-center font-bold text-gray-400 uppercase w-16">Urutan</th>
                                    <th class="px-4 py-2.5 text-left font-bold text-gray-500 uppercase">Ikon & Judul Artikel</th>
                                    <th class="px-4 py-2.5 text-left font-bold text-gray-500 uppercase">Slug URL</th>
                                    <th class="px-4 py-2.5 text-left font-bold text-gray-500 uppercase">Terakhir Diperbarui</th>
                                    <th class="px-4 py-2.5 text-right font-bold text-gray-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($items as $art)
                                    <tr class="hover:bg-slate-50/70">
                                        <td class="px-4 py-3 text-center font-bold text-gray-500">
                                            #{{ $art->order }}
                                        </td>
                                        <td class="px-4 py-3 font-bold text-gray-900 flex items-center gap-2">
                                            <span class="text-base">{{ $art->icon ?? '📄' }}</span>
                                            <span>{{ $art->title }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-500 font-mono text-[11px]">
                                            {{ $art->slug }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                                            {{ $art->updated_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap space-x-2">
                                            <a href="{{ route('documentation.index', ['version' => $version->version, 'article' => $art->slug]) }}" target="_blank"
                                                class="text-gray-600 hover:text-gray-900 font-bold">Lihat</a>
                                            <span class="text-gray-300">|</span>
                                            <a href="{{ route('admin.documentation.articles.edit', $art) }}"
                                                class="text-amber-600 hover:text-amber-800 font-bold">Edit</a>
                                            <span class="text-gray-300">|</span>
                                            <form action="{{ route('admin.documentation.articles.destroy', $art) }}" method="POST" class="inline"
                                                onsubmit="return confirm('Hapus artikel {{ $art->title }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-600 hover:text-rose-900 font-bold">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="bg-white p-12 rounded-2xl border border-gray-200 text-center text-gray-400">
                    <div class="text-4xl mb-2">📝</div>
                    <h4 class="font-bold text-gray-800 text-sm">Belum Ada Artikel dalam Versi Ini</h4>
                    <p class="text-xs text-gray-500 mt-1">Silakan tambahkan artikel baru untuk versi {{ $version->version }}.</p>
                    <a href="{{ route('admin.documentation.articles.create', $version) }}"
                        class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow transition">
                        <span>➕</span>
                        <span>Tambah Artikel Pertama</span>
                    </a>
                </div>
            @endforelse

        </div>
    </div>
</x-app-layout>
