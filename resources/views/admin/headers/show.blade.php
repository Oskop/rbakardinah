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
                    <div x-data="{ 
                        search: '',
                        formatIDR(val) {
                            return 'Rp ' + Number(val).toLocaleString('id-ID');
                        },
                        get totals() {
                            let totalUsulan = {{ $totalUsulan }};
                            let totalPagu = {{ $totalPagu }};
                            return { usulan: totalUsulan, pagu: totalPagu };
                        }
                    }">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                                <h3 class="text-lg font-bold">Laporan Hierarki RBA</h3>
                                <div class="relative">
                                    <input x-model="search" type="text" placeholder="Cari kode atau uraian..." 
                                        class="text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 w-72 pl-8">
                                    <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-8 bg-gray-50 p-4 rounded-xl border border-gray-200 shadow-sm transition-all hover:shadow-md">
                                <div class="flex space-x-8">
                                    <div class="text-right">
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-1">Total Usulan Global</p>
                                        <p class="text-2xl font-black text-indigo-700 leading-none" x-text="formatIDR(totals.usulan)"></p>
                                    </div>
                                    <div class="text-right border-l border-gray-300 pl-8">
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-1">Total Pagu Global</p>
                                        <p class="text-2xl font-black text-green-700 leading-none" x-text="formatIDR(totals.pagu)"></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4 flex flex-wrap gap-2">
                            <span class="text-xs font-bold text-gray-500 uppercase flex items-center">Status Unit:</span>
                            @foreach($header->submissions as $submission)
                                <span class="px-2 py-1 rounded-full text-[10px] font-bold border
                                        {{ $submission->status_submission === 'Draft' ? 'bg-gray-50 text-gray-600 border-gray-200' : '' }}
                                        {{ $submission->status_submission === 'Pending Supervisor' ? 'bg-yellow-50 text-yellow-700 border-yellow-200' : '' }}
                                        {{ $submission->status_submission === 'Validated' ? 'bg-green-50 text-green-700 border-green-200' : '' }}
                                    ">
                                    {{ $submission->unit->name }}: {{ $submission->status_submission }}
                                </span>
                            @endforeach
                        </div>

                        <div class="overflow-x-auto border border-gray-300 rounded-lg shadow-sm">
                            <table class="min-w-full border-collapse">
                                <thead>
                                    <tr class="bg-gray-100 border-b border-gray-300">
                                        <th class="border-r border-gray-300 px-4 py-3 text-left text-[10px] font-black uppercase tracking-wider text-gray-600">KODE REKENING</th>
                                        <th class="border-r border-gray-300 px-4 py-3 text-left text-[10px] font-black uppercase tracking-wider text-gray-600">URAIAN BELANJA</th>
                                        <th class="border-r border-gray-300 px-4 py-3 text-right text-[10px] font-black uppercase tracking-wider text-gray-600">USULAN (Rp)</th>
                                        <th class="border-r border-gray-300 px-4 py-3 text-right text-[10px] font-black uppercase tracking-wider text-gray-600">PAGU (Rp)</th>
                                        <th class="border-r border-gray-300 px-4 py-3 text-left text-[10px] font-black uppercase tracking-wider text-gray-600">SUPERVISOR</th>
                                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-wider text-gray-600">OPERATOR</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportData as $data)
                                        @php
                                            $isParent = $data['usulan'] > 0 || $data['pagu'] > 0;
                                            $hasDetails = $data['details']->count() > 0;
                                        @endphp
                                        <tr x-show="!search || $el.innerText.toLowerCase().includes(search.toLowerCase())"
                                            class="border-b border-gray-200 {{ $data['level'] <= 2 ? 'bg-gray-50/50 font-bold' : '' }} hover:bg-indigo-50/30 transition-colors">
                                            <td class="border-r border-gray-300 px-4 py-2 text-sm whitespace-nowrap font-mono text-gray-600">
                                                {{ $data['code'] }}
                                            </td>
                                            <td class="border-r border-gray-300 px-4 py-2 text-sm"
                                                style="padding-left: {{ 1 + ($data['level'] - 1) * 1 }}rem">
                                                {{ strtoupper($data['name']) }}
                                            </td>
                                            <td class="border-r border-gray-300 px-4 py-2 text-sm text-right {{ $data['level'] == 1 ? 'text-indigo-700' : '' }}">
                                                {{ number_format($data['usulan'], 0, ',', '.') }}
                                            </td>
                                            <td class="border-r border-gray-300 px-4 py-2 text-sm text-right {{ $data['level'] == 1 ? 'text-green-700' : '' }}">
                                                {{ number_format($data['pagu'], 0, ',', '.') }}
                                            </td>
                                            <td class="border-r border-gray-300 px-4 py-2 text-sm"></td>
                                            <td class="px-4 py-2 text-sm"></td>
                                        </tr>

                                        @if($hasDetails)
                                            @foreach($data['details'] as $detail)
                                                <tr x-show="!search || $el.innerText.toLowerCase().includes(search.toLowerCase())"
                                                    class="border-b border-gray-100 bg-white hover:bg-blue-50/50 transition-colors">
                                                    <td class="border-r border-gray-300 px-4 py-1.5 text-[11px] text-gray-400 italic"></td>
                                                    <td class="border-r border-gray-300 px-4 py-1.5 text-[11px] text-gray-700"
                                                        style="padding-left: {{ 2 + ($data['level'] - 1) * 1 }}rem">
                                                        <span class="text-indigo-400 mr-1">↳</span> {{ $detail->description }} 
                                                        <span class="ml-1 px-1.5 py-0.5 bg-gray-100 text-gray-500 rounded text-[9px] font-bold uppercase">{{ $detail->submission->unit->name }}</span>
                                                    </td>
                                                    <td class="border-r border-gray-300 px-4 py-1.5 text-[11px] text-right font-medium text-gray-600">
                                                        {{ number_format($detail->nominal_request, 0, ',', '.') }}
                                                    </td>
                                                    <td class="border-r border-gray-300 px-4 py-1.5 text-[11px] text-right text-gray-300">-</td>
                                                    <td class="border-r border-gray-300 px-4 py-1.5 text-[11px] text-gray-600">
                                                        {{ $detail->validator->name ?? '-' }}
                                                    </td>
                                                    <td class="px-4 py-1.5 text-[11px] text-gray-600">
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
    </div>
</x-app-layout>