<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan RBA Final dengan Pagu (Supervisor) - {{ $submission->unit->name ?? 'Unit' }} ({{ $submission->header->year ?? '' }})</title>
    <style>
        /* ==========================================================================
           SIPAKAR SUPERVISOR RBA FINAL REPORT PRINT TEMPLATE
           ========================================================================== */

        @page {
            size: A4 landscape;
            margin: 15mm 12mm 15mm 12mm;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #1e293b;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* Kop Surat & Header */
        .kop-header {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }

        .kop-table {
            width: 100%;
            border-collapse: collapse;
        }

        .kop-logo {
            width: 70px;
            text-align: center;
            vertical-align: middle;
        }

        .kop-logo img {
            max-height: 60px;
            width: auto;
        }

        .kop-text {
            text-align: center;
            vertical-align: middle;
        }

        .kop-instansi {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #475569;
            margin: 0;
        }

        .kop-rs {
            font-size: 16px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
            margin: 2px 0;
        }

        .kop-sub {
            font-size: 10px;
            color: #64748b;
            margin: 0;
        }

        .report-title {
            text-align: center;
            margin-bottom: 15px;
        }

        .report-title h2 {
            font-size: 13.5px;
            font-weight: 800;
            text-transform: uppercase;
            margin: 0 0 4px 0;
            color: #1e3a8a;
            letter-spacing: 0.5px;
        }

        .report-title p {
            font-size: 11px;
            color: #475569;
            margin: 0;
            font-weight: 600;
        }

        /* Information Grid */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }

        .meta-table td {
            padding: 6px 10px;
            font-size: 10.5px;
            vertical-align: top;
        }

        .meta-label {
            font-weight: 700;
            color: #475569;
            width: 15%;
        }

        .meta-val {
            color: #0f172a;
            width: 35%;
        }

        /* Section Styling */
        .section-title {
            font-size: 11.5px;
            font-weight: 800;
            color: #0f172a;
            border-left: 4px solid #0284c7;
            padding-left: 8px;
            margin: 15px 0 8px 0;
            text-transform: uppercase;
        }

        .background-box {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 10px 12px;
            margin-bottom: 15px;
            font-size: 10.5px;
            color: #334155;
            white-space: pre-line;
            text-align: justify;
        }

        /* Data Table Styling */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
        }

        .data-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-weight: 700;
            text-transform: uppercase;
            padding: 7px 6px;
            border: 1px solid #0f172a;
            text-align: center;
            vertical-align: middle;
            font-size: 9.5px;
        }

        .data-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            vertical-align: top;
        }

        .group-header-row td {
            background-color: #e2e8f0;
            font-weight: 800;
            color: #0f172a;
            padding: 6px 8px;
            border: 1px solid #94a3b8;
        }

        .subtotal-row td {
            background-color: #f1f5f9;
            font-weight: 700;
            color: #0f172a;
            border-top: 1.5px solid #64748b;
            border-bottom: 1.5px solid #64748b;
        }

        .grandtotal-row td {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 800;
            font-size: 10.5px;
            padding: 8px 6px;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: monospace, monospace; }

        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-success { background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-warning { background-color: #fef9c3; color: #a16207; border: 1px solid #fef08a; }
        .badge-danger { background-color: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }

        /* Signatures Section */
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            page-break-inside: avoid;
        }

        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 20px;
        }

        .sign-title {
            font-size: 10.5px;
            font-weight: 700;
            color: #475569;
            margin-bottom: 55px;
        }

        .sign-name {
            font-size: 11px;
            font-weight: 800;
            color: #0f172a;
            text-decoration: underline;
            margin: 0;
        }

        .sign-nip {
            font-size: 10px;
            color: #64748b;
            margin: 2px 0 0 0;
        }

        /* Web Print Controls Toolbar */
        .no-print-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background-color: #0f172a;
            color: #ffffff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 9999;
        }

        .btn-print {
            background-color: #0284c7;
            color: white;
            border: none;
            padding: 7px 16px;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
        }

        .btn-print:hover {
            background-color: #0369a1;
        }

        .btn-close {
            background-color: #475569;
            color: white;
            border: none;
            padding: 7px 14px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 11px;
            text-decoration: none;
        }

        .btn-close:hover {
            background-color: #334155;
        }

        @media print {
            .no-print-bar {
                display: none !important;
            }

            body {
                padding-top: 0 !important;
            }

            @page {
                margin: 12mm 10mm 12mm 10mm;
            }
        }
    </style>
</head>
<body style="padding-top: 55px;">

    <!-- Web Print Controls Toolbar -->
    <div class="no-print-bar">
        <div style="font-weight: 700; font-size: 13px; display: flex; items-center: center; gap: 8px;">
            <span>📊 PRATINJAU DOKUMEN CETAK RBA FINAL (SUPERVISOR)</span>
            <span style="font-size: 10px; background-color: #0284c7; color: white; padding: 2px 6px; border-radius: 4px;">PAGU FINAL</span>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <button onclick="window.print()" class="btn-print">
                🖨️ Cetak Dokumen (Ctrl+P)
            </button>
            <button onclick="window.close()" class="btn-close">
                Tutup
            </button>
        </div>
    </div>

    <!-- Kop Surat RSUD Kardinah -->
    <div class="kop-header">
        <table class="kop-table">
            <tr>
                <td class="kop-logo">
                    <img src="{{ asset('images/LogoSipakar.png') }}" alt="Logo SIPAKAR">
                </td>
                <td class="kop-text">
                    <div class="kop-instansi">Pemerintah Kota Tegal</div>
                    <div class="kop-rs">RSUD KARDINAH KOTA TEGAL</div>
                    <div class="kop-sub">Jl. Ks. Tubun No. 2 Tegal, Telp. (0283) 350477 Fax. (0283) 353131</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Judul Laporan Resmi -->
    <div class="report-title">
        <h2>USULAN RINCIAN RENCANA BELANJA DAN ANGGARAN DAN PAGU FINAL (RBA FINAL)</h2>
        <p>TAHUN ANGGARAN {{ $submission->header->year ?? date('Y') }} (PERIODE {{ strtoupper($submission->header->period->name ?? '') }})</p>
    </div>

    <!-- Informasi Metadata Grid -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">Unit Kerja Sub-Unit</td>
            <td class="meta-val">: <strong>{{ $submission->unit->name ?? '-' }}</strong> ({{ $submission->unit->code ?? '-' }})</td>
            <td class="meta-label">Supervisor / Atasan</td>
            <td class="meta-val">: {{ Auth::user()->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Filter Operator</td>
            <td class="meta-val">: <strong>{{ $operatorFilterLabel ?? 'Semua Operator' }}</strong></td>
            <td class="meta-label">Status Submisi</td>
            <td class="meta-val">: <span class="badge badge-success">{{ $submission->status_submission }}</span></td>
        </tr>
        <tr>
            <td class="meta-label">Opsi Cetak</td>
            <td class="meta-val">: {{ $includeBackground ? 'Dengan Latar Belakang Sub-Unit' : 'Tanpa Latar Belakang' }}</td>
            <td class="meta-label">Tanggal Cetak</td>
            <td class="meta-val">: {{ date('d F Y H:i') }} WIB</td>
        </tr>
    </table>

    <!-- Section I: Latar Belakang Sub-Unit (Kondisional) -->
    @if($includeBackground && !empty($filteredBackground ?? $submission->background))
        <div class="section-title">I. LATAR BELAKANG & ALASAN KEBUTUHAN SUB-UNIT</div>
        <div class="background-box">{{ $filteredBackground ?? $submission->background }}</div>
    @endif

    <!-- Section II: Tabel Rincian Belanja & Pagu Final Supervisor -->
    <div class="section-title">{{ ($includeBackground && !empty($submission->background)) ? 'II.' : 'I.' }} RINCIAN BELANJA DAN PAGU FINAL (RBA FINAL)</div>

    @php
        $groupedDetails = $submission->details->groupBy('account_code_id');
        $grandTotalUsulan = 0;
        $grandTotalPaguFinal = 0;
        $counter = 1;
    @endphp

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 3%;">NO</th>
                <th style="width: 10%;">KODE REKENING</th>
                <th style="width: 25%;">URAIAN & SPESIFIKASI BELANJA</th>
                <th style="width: 11%;">OPERATOR</th>
                <th style="width: 9%;">AWAL (Rp)</th>
                <th style="width: 4%;">VOL</th>
                <th style="width: 6%;">SATUAN</th>
                <th style="width: 8%;">HARGA SATUAN (Rp)</th>
                <th style="width: 10%;">TOTAL USULAN (Rp)</th>
                <th style="width: 9%;">PAGU FINAL (Rp)</th>
                <th style="width: 5%;">STATUS</th>
            </tr>
        </thead>
        <tbody>
            @forelse($groupedDetails as $accountCodeId => $details)
                @php
                    $firstDetail = $details->first();
                    $accountCode = $firstDetail->accountCode;
                    $paguFinal = $pagus[$accountCodeId]->nominal_pagu ?? 0;
                    $paguAwal = $previousPagus[$accountCodeId]->nominal_pagu ?? 0;
                    $subtotalUsulan = $details->sum('nominal_request');
                    $grandTotalUsulan += $subtotalUsulan;
                    $grandTotalPaguFinal += $paguFinal;
                @endphp

                <!-- Group Header Row -->
                <tr class="group-header-row">
                    <td class="text-center font-mono">{{ $counter++ }}</td>
                    <td class="font-mono">{{ $accountCode->code ?? '-' }}</td>
                    <td colspan="7">
                        <strong>{{ $accountCode->name ?? '-' }}</strong>
                    </td>
                    <td class="text-right font-mono" style="color: #0284c7; font-weight: 800;">
                        {{ number_format($paguFinal, 0, ',', '.') }}
                    </td>
                    <td class="text-center">-</td>
                </tr>

                <!-- Item Detail Rows -->
                @foreach($details as $detail)
                    <tr>
                        <td></td>
                        <td class="font-mono text-center" style="font-size: 8.5px; color: #64748b;">
                            {{ $accountCode->code ?? '-' }}
                        </td>
                        <td>
                            <div><strong>{{ $detail->description }}</strong></div>
                            @if(!empty($detail->spesifikasi))
                                <div style="font-size: 9px; color: #64748b; margin-top: 2px;">Spesifikasi: {{ $detail->spesifikasi }}</div>
                            @endif
                        </td>
                        <td>
                            <strong style="color: #0f172a;">{{ $detail->creator->name ?? 'System' }}</strong>
                        </td>
                        <td class="text-right font-mono">
                            {{ number_format($paguAwal, 0, ',', '.') }}
                        </td>
                        <td class="text-center font-mono">{{ $detail->volume }}</td>
                        <td class="text-center">{{ $detail->satuan }}</td>
                        <td class="text-right font-mono">{{ number_format($detail->harga_satuan, 0, ',', '.') }}</td>
                        <td class="text-right font-mono" style="font-weight: 700;">
                            {{ number_format($detail->nominal_request, 0, ',', '.') }}
                        </td>
                        <td class="text-right font-mono" style="color: #0369a1; font-weight: 600;">
                            {{ number_format($paguFinal, 0, ',', '.') }}
                        </td>
                        <td class="text-center">
                            @if($detail->is_validated)
                                <span class="badge badge-success">Valid</span>
                            @elseif($detail->is_rejected)
                                <span class="badge badge-danger">Tolak</span>
                            @else
                                <span class="badge badge-warning">Draft</span>
                            @endif
                        </td>
                    </tr>
                @endforeach

                <!-- Subtotal Row per Kode Rekening -->
                <tr class="subtotal-row">
                    <td colspan="4" class="text-right">
                        SUBTOTAL KODE REKENING [{{ $accountCode->code ?? '-' }}]:
                    </td>
                    <td class="text-right font-mono">{{ number_format($paguAwal, 0, ',', '.') }}</td>
                    <td colspan="3"></td>
                    <td class="text-right font-mono" style="font-weight: 800;">
                        {{ number_format($subtotalUsulan, 0, ',', '.') }}
                    </td>
                    <td class="text-right font-mono" style="font-weight: 800; color: #0284c7;">
                        {{ number_format($paguFinal, 0, ',', '.') }}
                    </td>
                    <td></td>
                </tr>

            @empty
                <tr>
                    <td colspan="11" class="text-center" style="padding: 20px; color: #64748b;">
                        Belum ada rincian belanja yang diusulkan untuk unit / filter operator ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($groupedDetails->count() > 0)
            <tfoot>
                <tr class="grandtotal-row">
                    <td colspan="8" class="text-right" style="letter-spacing: 0.5px;">
                        TOTAL KESELURUHAN RINCIAN BELANJA & PAGU FINAL:
                    </td>
                    <td class="text-right font-mono" style="font-size: 11px;">
                        Rp {{ number_format($grandTotalUsulan, 0, ',', '.') }}
                    </td>
                    <td class="text-right font-mono" style="font-size: 11px; color: #38bdf8;">
                        Rp {{ number_format($grandTotalPaguFinal, 0, ',', '.') }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>

    <!-- Tanda Tangan & Lembar Pengesahan -->
    <table class="signature-table">
        <tr>
            <td>
                <div class="sign-title">
                    Penyusun / Operator RBA Sub-Unit<br>
                    RSUD Kardinah Kota Tegal
                </div>
                <div class="sign-name">( {{ $operatorFilterLabel }} )</div>
                <div class="sign-nip">Operator Penyusun RBA</div>
            </td>
            <td>
                <div class="sign-title">
                    Tegal, {{ date('d F Y') }}<br>
                    Supervisor / Atasan Sub-Unit
                </div>
                <div class="sign-name">{{ Auth::user()->name ?? 'Supervisor Sub-Unit' }}</div>
                <div class="sign-nip">NIP. {{ Auth::user()->nip ?? '....................................' }}</div>
            </td>
        </tr>
    </table>

</body>
</html>
