<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Review Usulan RBA') }} - {{ $submission->header->year }} ({{ $submission->header->period->name }})
            </h2>
            <div class="flex space-x-2">
                @if($submission->status_submission === 'Pending Supervisor')
                    <form action="{{ route('supervisor.submissions.validate', $submission) }}" method="POST" onsubmit="return confirm('Validasi usulan ini?')">
                        @csrf
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm shadow">
                            Validasi & Lanjutkan
                        </button>
                    </form>
                @endif
                <a href="{{ route('supervisor.submissions.index') }}" class="py-2 px-4 text-sm text-gray-600 hover:text-gray-900">Kembali</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="font-bold text-lg mb-4">Daftar Rincian Belanja (Read-Only Review)</h3>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rekening</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase text-right">Usulan</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase text-right">Pagu Global</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status Pagu</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase text-center">PDF (Latest)</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase text-center">Validasi</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase text-center">History</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($submission->details as $detail)
                                <tr>
                                    <td class="px-4 py-2 text-sm">{{ $detail->accountCode->code }} - {{ $detail->accountCode->name }}</td>
                                    <td class="px-4 py-2 text-sm">{{ $detail->description }}</td>
                                    <td class="px-4 py-2 text-sm text-right">Rp {{ number_format($detail->nominal_request, 0, ',', '.') }}</td>
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
                                                <span class="text-red-600 font-bold">⚠️ OVER</span>
                                            @else
                                                <span class="text-green-600">Tercover</span>
                                            @endif
                                        @else
                                            <span class="text-yellow-600 italic">Pagu Belum Diset</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-sm text-center">
                                        @php $latest = $detail->latestAttachment(); @endphp
                                        @if($latest)
                                            <a href="{{ Storage::url($latest->file_path) }}" target="_blank" class="text-blue-600 hover:underline">
                                                Download V{{ $latest->version_number }}
                                            </a>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-sm text-center">
                                        <div class="flex flex-col items-center space-y-1">
                                            <div class="flex space-x-1">
                                                <!-- Validation Toggle -->
                                                <form action="{{ route('supervisor.details.toggle-validation', $detail) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center px-2 py-1 border rounded text-xs font-bold transition
                                                        {{ $detail->is_validated 
                                                            ? 'bg-green-100 text-green-800 border-green-300 hover:bg-green-200' 
                                                            : 'bg-gray-100 text-gray-800 border-gray-300 hover:bg-gray-200' }}">
                                                        {{ $detail->is_validated ? '✅ Valid' : '⏳ Valid' }}
                                                    </button>
                                                </form>

                                                <!-- Rejection Button -->
                                                @if(!$detail->is_validated)
                                                    <form action="{{ route('supervisor.details.reject', $detail) }}" method="POST" id="reject-form-{{ $detail->id }}">
                                                        @csrf
                                                        <input type="hidden" name="rejection_reason" id="rejection-reason-{{ $detail->id }}">
                                                        <button type="button" onclick="confirmRejection({{ $detail->id }})" 
                                                            class="inline-flex items-center px-2 py-1 border rounded text-xs font-bold transition
                                                            {{ $detail->is_rejected 
                                                                ? 'bg-red-200 text-red-900 border-red-400' 
                                                                : 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100' }}">
                                                            {{ $detail->is_rejected ? '❌ Ditolak' : '✖ Tolak' }}
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>

                                            @if($detail->is_validated)
                                                <div class="text-[10px] text-green-700 font-medium">
                                                    Divalidasi: {{ $detail->validator?->name }}<br>
                                                    {{ $detail->validated_at->format('d/m H:i') }}
                                                </div>
                                            @elseif($detail->is_rejected)
                                                <div class="text-[10px] text-red-700 font-medium max-w-[150px] truncate" title="{{ $detail->rejection_reason }}">
                                                    Ditolak: {{ $detail->rejector?->name }}<br>
                                                    Alasan: {{ $detail->rejection_reason }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-center">
                                        <a href="{{ route('history.show', $detail) }}" class="text-gray-600 hover:text-indigo-600 text-xs font-bold border rounded px-2 py-1">
                                            📜 Logs
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmRejection(id) {
            let reason = prompt("Masukkan alasan penolakan rincian ini:");
            if (reason && reason.trim() !== "") {
                document.getElementById('rejection-reason-' + id).value = reason;
                document.getElementById('reject-form-' + id).submit();
            } else if (reason !== null) {
                alert("Alasan penolakan wajib diisi.");
            }
        }
    </script>
</x-app-layout>
