<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Daftar Usulan RBA') }} - {{ Auth::user()->unit?->name ?? 'Belum Ditugaskan ke Unit' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Warning Banner when user is not yet assigned to any unit -->
            @if(!Auth::user()->unit_id)
                <div class="mb-6 p-5 bg-amber-50 border-l-4 border-amber-500 rounded-r-2xl shadow-sm text-amber-900">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl flex-shrink-0">⚠️</span>
                        <div>
                            <h3 class="font-bold text-sm text-amber-900">Akun Anda Belum Terhubung ke Unit Kerja</h3>
                            <p class="text-xs text-amber-700 mt-1 leading-relaxed">
                                Akun Anda telah aktif, namun Administrator belum menetapkan penugasan <strong>Unit Kerja</strong> untuk akun Anda di SIPAKAR. Silakan hubungi Administrator sistem untuk mengatur unit kerja Anda agar dapat mulai membuat dan mengelola usulan rincian belanja RBA.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            {{ session('success') }}
                        </div>
                    @endif

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th
                                    class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tahun / Periode</th>
                                <th
                                    class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($submissions as $submission)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ $submission->header->year }} - {{ $submission->header->period->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                {{ $submission->status_submission === 'Draft' ? 'bg-gray-100 text-gray-800' : 'bg-blue-100 text-blue-800' }}">
                                            {{ $submission->status_submission }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('operator.submissions.show', $submission) }}"
                                            class="text-indigo-600 hover:text-indigo-900">Buka Workboard</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-8 text-center text-sm text-gray-500">
                                        @if(!Auth::user()->unit_id)
                                            <div class="flex flex-col items-center justify-center gap-1">
                                                <span class="text-xl">🏢</span>
                                                <span class="font-medium text-gray-600">Belum ada usulan RBA</span>
                                                <span class="text-xs text-gray-400">Akun Anda belum memiliki penugasan Unit Kerja. Silakan hubungi Administrator.</span>
                                            </div>
                                        @else
                                            Belum ada usulan RBA untuk unit kerja Anda.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>