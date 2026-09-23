<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara Asistensi / Desk RAB {{ $submission->header->period->name ?? '' }} TA {{ $submission->header->year ?? '' }} - {{ $deskVerification->sub_unit_name }}</title>
    
    <style>
        /* CSS Reset & Print Configuration */
        @page {
            size: A4 portrait;
            margin: 15mm 15mm 15mm 15mm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.35;
            color: #000000;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        .no-print {
            display: block;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0 !important;
                background-color: #ffffff !important;
            }
            .page-container {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
            }
            .page-break-inside-avoid {
                page-break-inside: avoid;
            }
        }

        /* Screen Preview Toolbar */
        .preview-toolbar {
            background-color: #1e293b;
            color: #ffffff;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        .preview-toolbar h1 {
            font-size: 14px;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .preview-toolbar .btn-group {
            display: flex;
            gap: 10px;
        }

        .btn-print {
            background-color: #4f46e5;
            color: white;
            border: none;
            padding: 7px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.15s;
        }

        .btn-print:hover {
            background-color: #4338ca;
        }

        .btn-back {
            background-color: #475569;
            color: white;
            border: none;
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-back:hover {
            background-color: #334155;
        }

        /* Main Page Layout */
        .page-container {
            max-width: 210mm;
            margin: 20px auto;
            background: #ffffff;
            padding: 20mm 20mm 20mm 20mm;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            box-sizing: border-box;
        }

        /* Document Header */
        .doc-title {
            text-align: center;
            font-weight: bold;
            font-size: 13pt;
            margin-bottom: 20px;
            line-height: 1.3;
            text-transform: uppercase;
        }

        .intro-text {
            text-align: justify;
            margin-bottom: 12px;
            line-height: 1.4;
        }

        .sub-unit-header {
            font-weight: bold;
            margin-bottom: 14px;
        }

        /* Table Rekening Belanja */
        .rekening-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 10pt;
        }

        .rekening-table th, 
        .rekening-table td {
            border: 1px solid #000000;
            padding: 5px 8px;
            vertical-align: middle;
        }

        .rekening-table th {
            text-align: center;
            font-weight: bold;
            background-color: #f8fafc;
            text-transform: uppercase;
        }

        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }

        /* Notes & Evaluation Checklist */
        .notes-section {
            margin-top: 14px;
            margin-bottom: 14px;
            font-size: 10.5pt;
            line-height: 1.45;
        }

        .checklist-item {
            margin: 3px 0;
        }

        .perbaikan-box {
            margin: 4px 0 6px 20px;
            padding: 4px 8px;
            background-color: #f8fafc;
            border-left: 3px solid #000000;
            font-style: italic;
            font-size: 10pt;
        }

        /* Signatures Section */
        .signatures-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 24px;
            font-size: 10.5pt;
            page-break-inside: avoid;
            break-inside: avoid;
            box-sizing: border-box;
        }

        .signatures-table > tbody > tr > td {
            width: 50%;
            vertical-align: top;
            box-sizing: border-box;
        }

        .sign-column-left {
            padding-right: 15px;
            padding-left: 0;
        }

        .sign-column-right {
            padding-left: 15px;
            padding-right: 0;
        }

        .sign-header {
            font-weight: bold;
            margin-bottom: 12px;
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
            line-height: 1.35;
        }

        .sign-subtable {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }

        .sign-subtable td {
            border: none !important;
            box-sizing: border-box;
        }

        .sign-name-col {
            width: 62%;
            vertical-align: bottom;
            padding-bottom: 24px;
            padding-right: 6px;
            padding-left: 1.25em;
            text-indent: -1.25em;
            line-height: 1.35;
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
        }

        .sign-dots-col {
            width: 38%;
            vertical-align: bottom;
            padding-bottom: 24px;
            text-align: right;
            white-space: nowrap;
            font-family: 'Times New Roman', Times, serif;
            font-size: 10.5pt;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

    <!-- Screen Preview Toolbar -->
    <div class="preview-toolbar no-print">
        <h1>
            <span>📄</span>
            <span>Berita Acara Asistensi / Desk RBA - {{ $deskVerification->sub_unit_name }}</span>
        </h1>
        <div class="btn-group">
            <button onclick="window.print()" class="btn-print">
                <svg width="15" height="15" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd"/>
                </svg>
                <span>Cetak Dokumen (Print / Save PDF)</span>
            </button>
            <button onclick="window.close(); if(!window.closed) history.back();" class="btn-back">
                Kembali
            </button>
        </div>
    </div>

    <!-- Main Printable Sheet -->
    <div class="page-container">

        <!-- Title -->
        <div class="doc-title">
            BERITA ACARA ASISTENSI / DESK<br>
            RENCANA ANGGARAN BELANJA (RAB) {{ strtoupper($submission->header->period->name ?? 'PERUBAHAN') }}<br>
            TAHUN ANGGARAN {{ $submission->header->year ?? date('Y') }}
        </div>

        <!-- Opening Paragraph -->
        <div class="intro-text">
            Pada hari ini {{ $deskVerification->hari }}, {{ $deskVerification->tanggal_desk_spelled }} ({{ $deskVerification->tanggal_desk ? $deskVerification->tanggal_desk->format('d-m-Y') : date('d-m-Y') }}) bertempat di {{ $deskVerification->ruang_desk }}, telah dilaksanakan asistensi / desk RAB {{ $submission->header->period->name ?? 'Perubahan' }} Sub Bagian / Instalasi / Unit Tahun {{ $submission->header->year ?? date('Y') }} di lingkungan RSUD Kardinah Kota Tegal, dengan hasil sebagai berikut :
        </div>

        <!-- Sub Unit Name -->
        <div class="sub-unit-header">
            Nama Sub bagian / Instalasi / Unit : {{ $deskVerification->sub_unit_name }}
        </div>

        <!-- Table Rekening Belanja -->
        <table class="rekening-table">
            <thead>
                <tr>
                    <th style="width: 5%;">NO</th>
                    <th style="width: 47%;">REKENING BELANJA</th>
                    <th style="width: 16%;">AWAL</th>
                    <th style="width: 16%;">PERUBAHAN</th>
                    <th style="width: 16%;">SELISIH (+/-)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rekeningRows as $row)
                    <tr>
                        <td class="text-center">{{ $row['no'] }}</td>
                        <td class="text-left">{{ $row['name'] }}</td>
                        <td class="text-right">{{ number_format($row['awal'], 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($row['perubahan'], 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($row['selisih'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center" style="font-style: italic; color: #64748b; padding: 12px;">
                            Belum ada rincian belanja yang tercatat untuk unit/operator ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="font-bold" style="background-color: #f8fafc;">
                    <td colspan="2" class="text-center uppercase" style="letter-spacing: 0.5px;">TOTAL</td>
                    <td class="text-right">{{ number_format($totalAwal, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($totalPerubahan, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($totalSelisih, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- Notes & Evaluation Checklist -->
        <div class="notes-section">
            <div>{{ $deskVerification->catatan ?: 'Catatan hasil asistensi/desk terlampir.' }}</div>
            <div class="checklist-item">
                Usulan melalui SIPAKAR : <strong>{{ $deskVerification->is_usulan_sipakar ?? 'Ya' }}</strong>
            </div>
            <div class="checklist-item">
                Latar belakang sudah memenuhi 3 kriteria : <strong>{{ $deskVerification->kriteria_latar_belakang ?? 'Ya' }}</strong>
            </div>
            @if($deskVerification->kriteria_latar_belakang === 'Perlu Perbaikan' && !empty($deskVerification->catatan_perbaikan_latar_belakang))
                <div class="perbaikan-box">
                    <strong>Catatan Perbaikan Latar Belakang:</strong> {{ $deskVerification->catatan_perbaikan_latar_belakang }}
                </div>
            @endif
            <div class="checklist-item">
                Dokumen RAB diupload pada sistem : <strong>{{ $deskVerification->is_dokumen_rab_uploaded ?? 'Ya' }}</strong>
            </div>

            <div style="margin-top: 10px;">
                Demikian berita acara ini dibuat dan digunakan sebagaimana mestinya.
            </div>
        </div>

        <!-- Signatures Table -->
        <table class="signatures-table">
            <tbody>
                <tr>
                    <!-- Tim Asistensi (Left) -->
                    <td class="sign-column-left">
                        <div class="sign-header">Tim Asistensi :</div>
                        @php
                            $tim = is_array($deskVerification->tim_asistensi) && count($deskVerification->tim_asistensi) > 0 
                                ? $deskVerification->tim_asistensi 
                                : ['M. Riza F., A.Md.', 'Ananta Bayu, S.Kom', 'Nurul L. R., S.I.Pus.'];
                        @endphp
                        <table class="sign-subtable">
                            <tbody>
                                @foreach($tim as $index => $nama)
                                    <tr>
                                        <td class="sign-name-col">{{ $index + 1 }}. {{ $nama }}</td>
                                        <td class="sign-dots-col">(...................)</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>

                    <!-- Sub Unit Asistensi (Right) -->
                    <td class="sign-column-right">
                        <div class="sign-header">{{ $deskVerification->sub_unit_name }} :</div>
                        @php
                            $anggota = is_array($deskVerification->anggota_sub_unit) && count($deskVerification->anggota_sub_unit) > 0 
                                ? $deskVerification->anggota_sub_unit 
                                : [$targetUser->name ?? '...........................', '...........................'];
                        @endphp
                        <table class="sign-subtable">
                            <tbody>
                                @foreach($anggota as $index => $nama)
                                    <tr>
                                        <td class="sign-name-col">{{ $index + 1 }}. {{ $nama }}</td>
                                        <td class="sign-dots-col">(...................)</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>

    </div>

</body>
</html>
