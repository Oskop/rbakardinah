<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Rincian Belanja Usulan RBA - {{ $submission->unit->name ?? 'Unit' }} ({{ $submission->header->year ?? '' }})</title>
    <style>
        /* ==========================================================================
           SIPAKAR RBA REPORT PRINT TEMPLATE SYSTEM
           Developer Notes:
           - Template ini digunakan untuk Live Web Preview (browser print) dan mPDF export.
           - Menggunakan CSS mPDF & standard print CSS yang kompatibel dengan multi-halaman.
           - Pengaturan page-break-inside: avoid memastikan tabel tidak terpotong sembarangan.
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
            font-size: 14px;
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

        .meta-value {
            color: #0f172a;
            width: 35%;
        }

        /* Section Header */
        .section-header {
            font-size: 11.5px;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            border-left: 4px solid #2563eb;
            padding-left: 8px;
            margin: 15px 0 8px 0;
        }

        /* Background Text Box */
        .background-box {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 10px 12px;
            margin-bottom: 15px;
            font-size: 10.5px;
            color: #334155;
            white-space: pre-line;
            text-align: justify;
        }

        /* Data Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            page-break-inside: auto;
        }

        .data-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .data-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 7px 8px;
            border: 1px solid #0f172a;
            text-align: center;
        }

        .data-table td {
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            font-size: 10px;
            vertical-align: middle;
        }

        .data-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }

        .font-mono {
            font-family: 'Courier New', Courier, monospace;
        }

        .font-bold {
            font-weight: 700;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 8.5px;
            font-weight: 700;
            border-radius: 3px;
            text-transform: uppercase;
        }

        .badge-validated { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-pending { background-color: #fef9c3; color: #854d0e; border: 1px solid #fef08a; }
        .badge-rejected { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .badge-draft { background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

        /* Total Row Footer */
        .data-table tfoot td {
            background-color: #f1f5f9;
            font-weight: 800;
            font-size: 10.5px;
            border-top: 2px solid #0f172a;
            border-bottom: 2px solid #0f172a;
        }

        /* Signatures Section */
        .signatures-container {
            width: 100%;
            margin-top: 25px;
            page-break-inside: avoid;
        }

        .signatures-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signatures-table td {
            width: 50%;
            vertical-align: top;
            text-align: center;
            padding: 0 20px;
        }

        .sig-title {
            font-size: 10.5px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 50px;
        }

        .sig-name {
            font-size: 11px;
            font-weight: 800;
            color: #0f172a;
            text-decoration: underline;
            margin: 0;
        }

        .sig-role {
            font-size: 9.5px;
            color: #64748b;
            margin-top: 2px;
        }

        /* Web Print Toolbar (Screen Only) */
        @media screen {
            .no-print-bar {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                height: 50px;
                background-color: #0f172a;
                color: #ffffff;
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0 20px;
                box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
                z-index: 9999;
            }

            .no-print-bar h3 {
                margin: 0;
                font-size: 14px;
                font-weight: 600;
            }

            .btn-action {
                padding: 6px 14px;
                font-size: 12px;
                font-weight: 600;
                border-radius: 6px;
                border: none;
                cursor: pointer;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                transition: all 0.2s;
            }

            .btn-print { background-color: #2563eb; color: #ffffff; }
            .btn-print:hover { background-color: #1d4ed8; }
            .btn-pdf { background-color: #059669; color: #ffffff; }
            .btn-pdf:hover { background-color: #047857; }
            .btn-back { background-color: #475569; color: #ffffff; }
            .btn-back:hover { background-color: #334155; }

            body {
                padding-top: 70px;
                background-color: #f1f5f9;
            }

            .page-container {
                max-width: 1000px;
                margin: 0 auto 40px auto;
                background: #ffffff;
                padding: 30px;
                box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
                border-radius: 8px;
            }
        }

        @media print {
            .no-print-bar { display: none !important; }
            body { padding-top: 0; background-color: #ffffff; }
            .page-container { box-shadow: none; padding: 0; max-width: 100%; }
        }
    </style>
</head>
<body>

    <!-- Developer Web Preview Action Bar -->
    <div class="no-print-bar">
        <div>
            <h3>🖨️ Pratinjau Laporan Rincian Belanja RBA</h3>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="{{ route('operator.submissions.show', $submission->id) }}" class="btn-action btn-back">
                ← Kembali ke Workboard
            </a>
            <a href="{{ route('operator.submissions.export-pdf', ['submission' => $submission->id, 'include_background' => $includeBackground ? 1 : 0]) }}" class="btn-action btn-pdf">
                📄 Unduh PDF (mPDF Engine)
            </a>
            <button onclick="window.print()" class="btn-action btn-print">
                🖨️ Cetak via Browser
            </button>
        </div>
    </div>

    <div class="page-container">
        <!-- Kop Surat -->
        <div class="kop-header">
            <table class="kop-table">
                <tr>
                    <td class="kop-logo">
                        <img src="{{ public_path('images/LogoSipakar.png') }}" alt="Logo SIPAKAR" onerror="this.src='{{ asset('images/LogoSipakar.png') }}';">
                    </td>
                    <td class="kop-text">
                        <div class="kop-instansi">PEMERINTAH KOTA TEGAL</div>
                        <div class="kop-rs">RSUD KARDINAH KOTA TEGAL</div>
                        <div class="kop-sub">JL. KS. TUBUN NO. 2 TEGAL | TELP: (0283) 350477 / FAX: (0283) 353123</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Judul Laporan -->
        <div class="report-title">
            <h2>USULAN RINCIAN BELANJA RENCANA BISNIS DAN ANGGARAN (RBA)</h2>
            <p>TAHUN ANGGARAN {{ $submission->header->year ?? date('Y') }}</p>
        </div>

        <!-- Tabel Informasi Header -->
        <table class="meta-table">
            <tr>
                <td class="meta-label">UNIT KERJA</td>
                <td class="meta-value">: <strong>{{ $submission->unit->name ?? '-' }}</strong></td>
                <td class="meta-label">PERIODE RBA</td>
                <td class="meta-value">: {{ $submission->header->period->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="meta-label">OPERATOR PEMBUAT</td>
                <td class="meta-value">: {{ Auth::user()->name ?? '-' }} ({{ Auth::user()->email ?? '' }})</td>
                <td class="meta-label">TANGGAL CETAK</td>
                <td class="meta-value">: {{ now()->translatedFormat('d F Y H:i') }} WIB</td>
            </tr>
            <tr>
                <td class="meta-label">STATUS SUBMISSION</td>
                <td class="meta-value">: <strong>{{ $submission->status_submission ?? 'Draft' }}</strong></td>
                <td class="meta-label">OPSI CETAK</td>
                <td class="meta-value">: {{ $includeBackground ? 'Dengan Latar Belakang' : 'Tanpa Latar Belakang' }}</td>
            </tr>
        </table>

        @php $sectionIndex = 1; @endphp

        <!-- Section I: Latar Belakang (Jika Opsi Diberdayakan & Ada Teks) -->
        @if($includeBackground)
            <div class="section-header">{{ $sectionIndex++ }}. LATAR BELAKANG SUB-UNIT</div>
            <div class="background-box">
                @if(!empty($submission->background))
                    {{ $submission->background }}
                @else
                    <em>(Latar belakang belum diisi oleh Operator)</em>
                @endif
            </div>
        @endif

        <!-- Section II / I: Tabel Rincian Belanja -->
        <div class="section-header">{{ $sectionIndex++ }}. RINCIAN BELANJA USULAN OPERATOR</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 3%;">NO</th>
                    <th style="width: 10%;">KODE REKENING</th>
                    <th style="width: 25%;">URAIAN & SPESIFIKASI BELANJA</th>
                    <th style="width: 11%;">AWAL (Rp)</th>
                    <th style="width: 7%;">VOL</th>
                    <th style="width: 7%;">SATUAN</th>
                    <th style="width: 11%;">HARGA SATUAN (Rp)</th>
                    <th style="width: 14%;">TOTAL USULAN (Rp)</th>
                    <th style="width: 12%;">STATUS</th>
                </tr>
            </thead>
            <tbody>
                @php 
                    $totalUsulan = 0;
                    $totalAwal = 0;
                @endphp
                @forelse($submission->details as $index => $detail)
                    @php 
                        $totalUsulan += $detail->nominal_request;
                        $previousPagu = $previousPagus[$detail->account_code_id]->nominal_pagu ?? 0;
                        $totalAwal += $previousPagu;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="font-mono text-center font-bold">{{ $detail->accountCode->code ?? '-' }}</td>
                        <td class="text-left">
                            <strong>{{ $detail->accountCode->name ?? '-' }}</strong>
                            @if(!empty($detail->description))
                                <br><span style="color: #475569; font-size: 9.5px;">{{ $detail->description }}</span>
                            @endif
                        </td>
                        <td class="text-right font-mono">
                            {{ $previousPagu > 0 ? number_format($previousPagu, 0, ',', '.') : '-' }}
                        </td>
                        <td class="text-center font-mono">{{ number_format($detail->volume ?? 1, 2, ',', '.') }}</td>
                        <td class="text-center">{{ $detail->satuan ?? 'Pkt' }}</td>
                        <td class="text-right font-mono">Rp {{ number_format($detail->harga_satuan ?? 0, 0, ',', '.') }}</td>
                        <td class="text-right font-mono font-bold">Rp {{ number_format($detail->nominal_request, 0, ',', '.') }}</td>
                        <td class="text-center">
                            @if($detail->is_validated)
                                <span class="badge badge-validated">✓ Validated</span>
                            @elseif($detail->is_rejected)
                                <span class="badge badge-rejected">✖ Rejected</span>
                            @elseif($detail->is_submitted)
                                <span class="badge badge-pending">⌛ Pending</span>
                            @else
                                <span class="badge badge-draft">Draft</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center" style="padding: 20px; color: #64748b; font-style: italic;">
                            Belum ada rincian belanja usulan yang diinput oleh operator.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right">TOTAL AKUMULASI USULAN:</td>
                    <td class="text-right font-mono font-bold">
                        {{ $totalAwal > 0 ? 'Rp ' . number_format($totalAwal, 0, ',', '.') : '-' }}
                    </td>
                    <td colspan="3"></td>
                    <td class="text-right font-mono font-bold" style="color: #1e3a8a;">
                        Rp {{ number_format($totalUsulan, 0, ',', '.') }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <!-- Lembar Pengesahan / Tanda Tangan -->
        <div class="signatures-container">
            <table class="signatures-table">
                <tr>
                    <td>
                        <div class="sig-title">
                            Menyetujui,<br>
                            <strong>Supervisor / Atasan Sub-Unit</strong>
                        </div>
                        <div class="sig-name">( ___________________________ )</div>
                        <div class="sig-role">NIP. ....................................................</div>
                    </td>
                    <td>
                        <div class="sig-title">
                            Tegal, {{ now()->translatedFormat('d F Y') }}<br>
                            <strong>Operator / Penyusun RBA</strong>
                        </div>
                        <div class="sig-name">{{ Auth::user()->name ?? '( ___________________________ )' }}</div>
                        <div class="sig-role">NIP. {{ Auth::user()->nip ?? '....................................................' }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

</body>
</html>
