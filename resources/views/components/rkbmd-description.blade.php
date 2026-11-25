@props([
    'notes' => null,
    'limit' => 150,
])

@php
    $trimmedNotes = trim((string) ($notes ?? ''));
    $isLong = $trimmedNotes !== '' && mb_strlen($trimmedNotes) > $limit;
    $previewNotes = $isLong ? \Illuminate\Support\Str::limit($trimmedNotes, $limit, '') : $trimmedNotes;
@endphp

@if($trimmedNotes !== '')
    @if($isLong)
        <div class="rkbmd-desc-container text-xs text-slate-500 mt-1 break-words whitespace-normal leading-relaxed rkbmd-notes-wrap" title="{{ $trimmedNotes }}">
            <span class="rkbmd-desc-trimmed">
                {{ $previewNotes }}<span class="text-slate-400">...</span>
                <button type="button"
                    data-rkbmd-toggle="expand"
                    class="rkbmd-desc-toggle text-indigo-600 hover:text-indigo-800 font-semibold hover:underline inline cursor-pointer ml-0.5 focus:outline-none">(selengkapnya...)</button>
            </span>
            <span class="rkbmd-desc-full hidden">
                {{ $trimmedNotes }}
                <button type="button"
                    data-rkbmd-toggle="collapse"
                    class="rkbmd-desc-toggle text-indigo-600 hover:text-indigo-800 font-semibold hover:underline inline cursor-pointer ml-1 focus:outline-none">(ringkas)</button>
            </span>
        </div>
    @else
        <div class="text-xs text-slate-500 mt-1 break-words whitespace-normal leading-relaxed rkbmd-notes-wrap" title="{{ $trimmedNotes }}">
            {{ $trimmedNotes }}
        </div>
    @endif
@endif
