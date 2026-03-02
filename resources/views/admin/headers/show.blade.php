<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('RBA Submissions') }} - {{ $header->year }} ({{ $header->period->name }})
            </h2>
            <div class="flex items-center space-x-4">
                <a href="{{ route('admin.headers.pagu.index', $header) }}"
                    class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm shadow">
                    Set Pagu Global
                </a>
                <a href="{{ route('admin.headers.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Back to
                    List</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
                <div class="p-6 text-gray-900">
                    <div class="text-center mb-8 border-b-2 border-gray-800 pb-4">
                        <h1 class="text-xl font-bold uppercase">DRAFT RENCANA BELANJA DAN ANGGARAN (RBA) BLUD RSUD KARDINAH KOTA TEGAL TAHUN {{ $header->year }}</h1>
                    </div>
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold">Laporan Hierarki RBA</h3>
                        <div class="text-sm text-gray-600">
                            Status Unit:
                            @foreach($header->submissions as $submission)
                                <span class="ml-2 px-2 py-1 rounded-full text-xs font-semibold
                                        {{ $submission->status_submission === 'Draft' ? 'bg-gray-100 text-gray-800' : '' }}
                                        {{ $submission->status_submission === 'Pending Supervisor' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $submission->status_submission === 'Validated' ? 'bg-green-100 text-green-800' : '' }}
                                    ">
                                    {{ $submission->unit->name }}: {{ $submission->status_submission }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <div class="overflow-x-auto border border-gray-300 rounded-lg">
                        <table class="min-w-full border-collapse">
                            <thead>
                                <tr class="bg-gray-100 border-b border-gray-300">
                                    <th
                                        class="border-r border-gray-300 px-4 py-2 text-left text-xs font-bold uppercase">
                                        KODE REKENING</th>
                                    <th
                                        class="border-r border-gray-300 px-4 py-2 text-left text-xs font-bold uppercase">
                                        URAIAN BELANJA</th>
                                    <th
                                        class="border-r border-gray-300 px-4 py-2 text-right text-xs font-bold uppercase">
                                        USULAN (Rp)</th>
                                    <th
                                        class="border-r border-gray-300 px-4 py-2 text-right text-xs font-bold uppercase">
                                        PAGU (Rp)</th>
                                    <th
                                        class="border-r border-gray-300 px-4 py-2 text-left text-xs font-bold uppercase">
                                        SUPERVISOR</th>
                                    <th class="px-4 py-2 text-left text-xs font-bold uppercase">OPERATOR</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reportData as $data)
                                    @php
                                        $isParent = $data['usulan'] > 0 || $data['pagu'] > 0;
                                        $hasDetails = $data['details']->count() > 0;
                                        $indentLevel = ($data['level'] - 1) * 4;
                                    @endphp
                                    <tr
                                        class="border-b border-gray-200 {{ $data['level'] <= 2 ? 'bg-gray-50 font-bold' : '' }}">
                                        <td class="border-r border-gray-300 px-4 py-2 text-sm whitespace-nowrap">
                                            {{ $data['code'] }}
                                        </td>
                                        <td class="border-r border-gray-300 px-4 py-2 text-sm"
                                            style="padding-left: {{ 1 + ($data['level'] - 1) * 1 }}rem">
                                            {{ strtoupper($data['name']) }}
                                        </td>
                                        <td class="border-r border-gray-300 px-4 py-2 text-sm text-right">
                                            {{ number_format($data['usulan'], 0, ',', '.') }}
                                        </td>
                                        <td class="border-r border-gray-300 px-4 py-2 text-sm text-right">
                                            {{ number_format($data['pagu'], 0, ',', '.') }}
                                        </td>
                                        <td class="border-r border-gray-300 px-4 py-2 text-sm">
                                            {{-- If it's a parent code with direct details, we show them below.
                                            But for the account code row itself, we only show supervisor if there's exactly
                                            one common one --}}
                                        </td>
                                        <td class="px-4 py-2 text-sm">
                                        </td>
                                    </tr>

                                    @if($hasDetails)
                                        @foreach($data['details'] as $detail)
                                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                                <td class="border-r border-gray-300 px-4 py-1 text-xs text-gray-500 italic">
                                                    {{-- Sub-code or just empty --}}
                                                </td>
                                                <td class="border-r border-gray-300 px-4 py-1 text-xs text-gray-600 italic"
                                                    style="padding-left: {{ 2 + ($data['level'] - 1) * 1 }}rem">
                                                    - {{ $detail->description }} ({{ $detail->submission->unit->name }})
                                                </td>
                                                <td class="border-r border-gray-300 px-4 py-1 text-xs text-right text-gray-600">
                                                    {{ number_format($detail->nominal_request, 0, ',', '.') }}
                                                </td>
                                                <td class="border-r border-gray-300 px-4 py-1 text-xs text-right text-gray-600">
                                                    -
                                                </td>
                                                <td class="border-r border-gray-300 px-4 py-1 text-xs text-gray-600">
                                                    {{ $detail->validator->name ?? '-' }}
                                                </td>
                                                <td class="px-4 py-1 text-xs text-gray-600">
                                                    {{ $detail->creator->name ?? '-' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>