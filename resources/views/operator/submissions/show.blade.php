<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Workboard RBA') }} - {{ $submission->header->year }} ({{ $submission->header->period->name }})
            </h2>
            <div class="flex space-x-2">
                @if($submission->status_submission === 'Draft')
                    <a href="{{ route('operator.details.create', ['submission_id' => $submission->id]) }}"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">
                        + Tambah Rincian
                    </a>
                    <form action="{{ route('operator.submissions.submit', $submission) }}" method="POST"
                        onsubmit="return confirm('Ajukan usulan ini ke Supervisor?')">
                        @csrf
                        <button type="submit"
                            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm">
                            Ajukan ke Supervisor
                        </button>
                    </form>
                @endif
                <a href="{{ route('operator.submissions.index') }}"
                    class="py-2 px-4 text-sm text-gray-600 hover:text-gray-900">Kembali</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="font-bold text-lg mb-4">Rincian Belanja</h3>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rekening
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase text-right">
                                    Usulan (Unit)</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase text-right">
                                    Pagu Global</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status Pagu
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase text-center">
                                    PDF</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($submission->details as $detail)
                                <tr>
                                    <td class="px-4 py-2 text-sm">{{ $detail->accountCode->code }} -
                                        {{ $detail->accountCode->name }}
                                    </td>
                                    <td class="px-4 py-2 text-sm">{{ $detail->description }}</td>
                                    <td class="px-4 py-2 text-sm text-right">Rp
                                        {{ number_format($detail->nominal_request, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-right">
                                        @if(isset($pagus[$detail->account_code_id]))
                                            Rp {{ number_format($pagus[$detail->account_code_id]->nominal_pagu, 0, ',', '.') }}
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-sm">
                                        @if(isset($pagus[$detail->account_code_id]))
                                            @php 
                                                $pagu = $pagus[$detail->account_code_id]->nominal_pagu;
                                                $total = $headerTotals[$detail->account_code_id]->total ?? 0;
                                            @endphp
                                            @if($total > $pagu)
                                                <span class="text-red-600 font-bold">MELEBIHI PAGU</span>
                                                <div class="text-xs text-red-500">(Over: Rp {{ number_format($total - $pagu, 0, ',', '.') }})</div>
                                            @else
                                                <span class="text-green-600 font-semibold">✓ Tercover</span>
                                            @endif
                                        @else
                                            <span class="text-yellow-600 italic">Pagu Belum Diset</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-sm text-center">
                                        @php $latest = $detail->latestAttachment(); @endphp
                                        @if($latest)
                                            <a href="{{ Storage::url($latest->file_path) }}" target="_blank"
                                                class="text-blue-600 hover:underline">
                                                Download PDF (V{{ $latest->version_number }})
                                            </a>
                                            @if($detail->attachments->count() > 1)
                                                <div class="text-xs text-gray-400">Total {{ $detail->attachments->count() }} versi
                                                </div>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-sm">
                                        @if($submission->status_submission === 'Draft')
                                            <div class="flex flex-col space-y-2">
                                                <a href="{{ route('operator.details.edit', $detail) }}"
                                                    class="text-indigo-600 hover:text-indigo-900 text-xs font-bold">Edit
                                                    Detail</a>
                                                <form action="{{ route('operator.details.upload-version', $detail) }}"
                                                    method="POST" enctype="multipart/form-data"
                                                    class="flex items-center space-x-1 border-t pt-2">
                                                    @csrf
                                                    <input type="file" name="attachment" class="text-xs w-32" required>
                                                    <button type="submit"
                                                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-1 px-2 rounded text-xs">Revisi
                                                        PDF</button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-gray-400">Locked</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-2 text-center text-gray-500">Belum ada rincian belanja.
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