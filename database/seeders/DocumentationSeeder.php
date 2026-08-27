<?php

namespace Database\Seeders;

use App\Models\DocumentationArticle;
use App\Models\DocumentationVersion;
use App\Models\User;
use Illuminate\Database\Seeder;

class DocumentationSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'Administrator')->first() ?? User::first();

        // 1. Create Active HTML Version (v1.0.0)
        $htmlVersion = DocumentationVersion::updateOrCreate(
            ['type' => 'html', 'version' => 'v1.0.0'],
            [
                'title' => 'Buku Panduan Penggunaan Sistem Informasi RBA RSUD Kardinah',
                'release_notes' => 'Rilis perdana dokumentasi resmi sistem RBA berbasis web interaktif dengan tata letak modern.',
                'released_at' => '2026-08-27',
                'is_active' => true,
                'created_by' => $admin?->id,
                'updated_by' => $admin?->id,
            ]
        );

        // 2. Create Active PDF Version (v1.0.0)
        DocumentationVersion::updateOrCreate(
            ['type' => 'pdf', 'version' => 'v1.0.0'],
            [
                'title' => 'Manual Book PDF Resmi RBA RSUD Kardinah',
                'file_path' => 'documents/manual_book_rba_v1.0.pdf',
                'file_size' => 2048576, // 2 MB
                'release_notes' => 'Edisi cetak pertama format PDF Buku Panduan Penggunaan Aplikasi RBA.',
                'released_at' => '2026-08-27',
                'is_active' => true,
                'created_by' => $admin?->id,
                'updated_by' => $admin?->id,
            ]
        );

        // Articles Definition
        $articles = [
            // Category: 🚀 Pengenalan
            [
                'category' => '🚀 Pengenalan',
                'title' => 'Tentang Aplikasi RBA',
                'slug' => 'tentang-aplikasi-rba',
                'icon' => '🏢',
                'order' => 1,
                'content' => <<<'HTML'
<h2>Gambaran Umum Sistem</h2>
<p>Sistem Informasi <strong>Rencana Bisnis dan Anggaran (RBA) RSUD Kardinah Kota Tegal</strong> adalah platform digital terpadu yang dirancang untuk mengelola seluruh tahapan penyusunan, pengusulan, verifikasi, validasi, hingga penetapan pagu belanja anggaran operasional rumah sakit.</p>

<div class="admonition admonition-info">
    <div class="admonition-title">ℹ️ Tujuan Utama Sistem</div>
    <div class="admonition-content">
        Meningkatkan transparansi, akuntabilitas, efisiensi waktu penyusunan anggaran, serta mencegah terjadinya pengusulan anggaran yang melebihi batas pagu indikatif nomor rekening belanja.
    </div>
</div>

<h2>Arsitektur Peran Pengguna (Roles)</h2>
<p>Aplikasi ini membagi hak akses ke dalam 3 tingkatan peran utama:</p>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 my-6">
    <div class="p-4 rounded-xl border border-blue-200 bg-blue-50/50">
        <div class="text-sm font-bold text-blue-900 flex items-center gap-1.5">
            <span>👷</span> Operator Unit
        </div>
        <p class="text-xs text-blue-800 mt-2 leading-relaxed">
            Menyusun latar belakang unit, menginput usulan rincian belanja, mengunggah lampiran PDF, dan merevisi usulan jika ditolak.
        </p>
    </div>
    <div class="p-4 rounded-xl border border-indigo-200 bg-indigo-50/50">
        <div class="text-sm font-bold text-indigo-900 flex items-center gap-1.5">
            <span>🔍</span> Supervisor Unit
        </div>
        <p class="text-xs text-indigo-800 mt-2 leading-relaxed">
            Mereview seluruh usulan dari operator dalam unit kerjanya, melakukan validasi usulan yang sesuai, atau menolak usulan yang butuh perbaikan.
        </p>
    </div>
    <div class="p-4 rounded-xl border border-purple-200 bg-purple-50/50">
        <div class="text-sm font-bold text-purple-900 flex items-center gap-1.5">
            <span>👑</span> Administrator
        </div>
        <p class="text-xs text-purple-800 mt-2 leading-relaxed">
            Mengelola data master (Unit, Rekening, Periode), menetapkan pagu per nomor rekening, dan memantau Log Data transaksi.
        </p>
    </div>
</div>
HTML
            ],
            [
                'category' => '🚀 Pengenalan',
                'title' => 'Alur Bisnis Utama (Workflow)',
                'slug' => 'alur-bisnis-utama',
                'icon' => '🔄',
                'order' => 2,
                'content' => <<<'HTML'
<h2>Diagram Alur Kerja Penyusunan RBA</h2>
<p>Berikut adalah alur perjalanan pengusulan rincian belanja dari awal hingga penetapan pagu final:</p>

<div class="step-card">
    <div class="step-badge">1</div>
    <div class="step-content">
        <h4>Pengisian Latar Belakang & Usulan oleh Operator</h4>
        <p>Operator unit mengisi latar belakang penyusunan RBA, kemudian menginput rincian belanja beserta lampiran berkas PDF spesifikasi/RAB.</p>
    </div>
</div>

<div class="step-card">
    <div class="step-badge">2</div>
    <div class="step-content">
        <h4>Pengajuan Usulan ke Supervisor</h4>
        <p>Setelah usulan lengkap, Operator mengklik tombol <strong>Ajukan</strong> agar usulan masuk ke antrean verifikasi Supervisor.</p>
    </div>
</div>

<div class="step-card">
    <div class="step-badge">3</div>
    <div class="step-content">
        <h4>Review & Validasi oleh Supervisor</h4>
        <p>Supervisor memeriksa kesesuaian harga dan lampiran PDF. Supervisor berhak memvalidasi usulan atau menolaknya dengan menyertakan alasan penolakan.</p>
    </div>
</div>

<div class="step-card">
    <div class="step-badge">4</div>
    <div class="step-content">
        <h4>Penetapan Pagu oleh Administrator</h4>
        <p>Setelah seluruh usulan unit kerja divalidasi oleh Supervisor, Administrator dapat menetapkan nominal pagu pada nomor rekening terkait.</p>
    </div>
</div>

<div class="admonition admonition-tip">
    <div class="admonition-title">💡 Prinsip Penguncian Otomatis</div>
    <div class="admonition-content">
        Setelah sebuah usulan divalidasi oleh Supervisor, usulan tersebut terkunci otomatis (tidak dapat diedit/dihapus oleh operator). Dan setelah nomor rekening ditetapkan pagunya oleh Administrator, penambahan usulan baru pada rekening tersebut otomatis ditutup.
    </div>
</div>
HTML
            ],

            // Category: 📝 Panduan Operator
            [
                'category' => '📝 Panduan Operator',
                'title' => 'Pengisian Latar Belakang Unit',
                'slug' => 'pengisian-latar-belakang-unit',
                'icon' => '📄',
                'order' => 3,
                'content' => <<<'HTML'
<h2>Kewajiban Pengisian Latar Belakang</h2>
<p>Sebelum dapat menginput rincian belanja, Operator unit kerja <strong>wajib</strong> mengisi narasi latar belakang kebutuhan anggaran unit pada tahun berjalan.</p>

<div class="admonition admonition-warning">
    <div class="admonition-title">⚠️ Perhatian</div>
    <div class="admonition-content">
        Form input rincian belanja tidak akan dapat disimpan dan sistem akan menampilkan pesan peringatan apabila narasi latar belakang unit masih kosong.
    </div>
</div>

<h2>Langkah-langkah Pengisian</h2>
<ol class="list-decimal pl-5 space-y-2 my-4 text-sm text-gray-700">
    <li>Masuk ke menu <strong>Workboard RBA</strong> di navigasi atas.</li>
    <li>Pilih periode / tahun anggaran aktif yang sedang dibuka.</li>
    <li>Pada panel <strong>Latar Belakang Unit</strong> di sebelah atas, klik tombol <strong>Edit Latar Belakang</strong>.</li>
    <li>Tuliskan narasi penjelasan urgensi, rencana kegiatan, atau dasar kebutuhan belanja unit.</li>
    <li>Klik tombol <strong>💾 Simpan Latar Belakang</strong>.</li>
</ol>
HTML
            ],
            [
                'category' => '📝 Panduan Operator',
                'title' => 'Input Usulan Rincian Belanja',
                'slug' => 'input-usulan-rincian-belanja',
                'icon' => '✍️',
                'order' => 4,
                'content' => <<<'HTML'
<h2>Formulir Input Rincian Belanja</h2>
<p>Setelah latar belakang tersimpan, Operator dapat mulai menginput item-item usulan belanja.</p>

<h2>Parameter yang Wajib Diisi</h2>
<div class="overflow-x-auto my-4">
    <table class="min-w-full divide-y divide-gray-200 text-xs">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-3 py-2 text-left font-bold text-gray-600">Nama Field</th>
                <th class="px-3 py-2 text-left font-bold text-gray-600">Tipe Data</th>
                <th class="px-3 py-2 text-left font-bold text-gray-600">Keterangan</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            <tr>
                <td class="px-3 py-2 font-semibold text-gray-900">Nomor Rekening</td>
                <td class="px-3 py-2 text-gray-600">Pilihan Dropdown</td>
                <td class="px-3 py-2 text-gray-600">Pilih kode rekening belanja yang sesuai (misal: 5.1.02.01.01.0001).</td>
            </tr>
            <tr>
                <td class="px-3 py-2 font-semibold text-gray-900">Uraian / Deskripsi</td>
                <td class="px-3 py-2 text-gray-600">Teks</td>
                <td class="px-3 py-2 text-gray-600">Rincian nama barang, jasa, atau spesifikasi item belanja.</td>
            </tr>
            <tr>
                <td class="px-3 py-2 font-semibold text-gray-900">Volume & Satuan</td>
                <td class="px-3 py-2 text-gray-600">Angka & Teks</td>
                <td class="px-3 py-2 text-gray-600">Jumlah kuantitas (misal: 10) dan satuan barang (misal: Box, Unit, Rim).</td>
            </tr>
            <tr>
                <td class="px-3 py-2 font-semibold text-gray-900">Harga Satuan (Rp)</td>
                <td class="px-3 py-2 text-gray-600">Mata Uang (Nominal)</td>
                <td class="px-3 py-2 text-gray-600">Harga per satu unit barang. Total nominal dihitung otomatis.</td>
            </tr>
            <tr>
                <td class="px-3 py-2 font-semibold text-gray-900">Lampiran PDF</td>
                <td class="px-3 py-2 text-gray-600">File PDF (Maks. 10MB)</td>
                <td class="px-3 py-2 text-gray-600">Berkas KAK / RAB / brosur harga penawaran dalam format PDF.</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="admonition admonition-tip">
    <div class="admonition-title">💡 Status Awal Usulan</div>
    <div class="admonition-content">
        Setiap item yang baru selesai diinput akan memiliki status <strong>Draft</strong>. Usulan berstatus Draft belum terlihat di antrean Supervisor sebelum Operator menekan tombol <strong>Ajukan</strong>.
    </div>
</div>
HTML
            ],
            [
                'category' => '📝 Panduan Operator',
                'title' => 'Revisi PDF Usulan Ditolak',
                'slug' => 'revisi-pdf-usulan-ditolak',
                'icon' => '🔄',
                'order' => 5,
                'content' => <<<'HTML'
<h2>Penanganan Usulan Berstatus Tolak</h2>
<p>Jika usulan yang diajukan dinilai kurang lengkap atau nominal tidak wajar oleh Supervisor, usulan akan berstatus <strong>Tolak</strong> disertai alasan penolakan tertulis.</p>

<h2>Langkah-langkah Revisi</h2>
<ol class="list-decimal pl-5 space-y-3 my-4 text-sm text-gray-700">
    <li>Buka tabel usulan belanja pada halaman Workboard RBA.</li>
    <li>Temukan baris dengan badge merah <strong>Tolak</strong> dan baca catatan alasan penolakan.</li>
    <li>Pada kolom aksi baris tersebut, pilih file PDF baru yang telah diperbaiki pada form <strong>Revisi</strong>.</li>
    <li>Klik tombol <strong>Revisi</strong>.</li>
    <li>Sistem akan otomatis mengunggah versi PDF terbaru (V2, V3, dst.) dan mengembalikan status usulan menjadi <strong>Draft</strong>.</li>
    <li>Klik tombol <strong>Ajukan</strong> untuk mengirimkan kembali usulan yang telah direvisi ke Supervisor.</li>
</ol>
HTML
            ],

            // Category: 🔍 Panduan Supervisor
            [
                'category' => '🔍 Panduan Supervisor',
                'title' => 'Review Usulan Unit Kerja',
                'slug' => 'review-usulan-unit-kerja',
                'icon' => '📋',
                'order' => 6,
                'content' => <<<'HTML'
<h2>Tugas & Tanggung Jawab Supervisor</h2>
<p>Supervisor bertindak sebagai verifikator tingkat pertama untuk seluruh usulan yang diajukan oleh Operator di unit kerjanya.</p>

<h2>Menu Review RBA</h2>
<p>Buka menu <strong>Review RBA</strong> di navigasi atas. Di halaman ini ditampilkan ringkasan unit kerja, total nominal pengusulan, dan tabel daftar rincian usulan belanja yang telah diajukan.</p>

<div class="admonition admonition-info">
    <div class="admonition-title">ℹ️ Item yang Tampil</div>
    <div class="admonition-content">
        Supervisor hanya melihat usulan yang telah diajukan oleh Operator (status <em>Ajuan</em>, <em>Valid</em>, atau <em>Tolak</em>). Usulan yang masih berstatus <em>Draft</em> tidak akan muncul di meja verifikasi Supervisor.
    </div>
</div>
HTML
            ],
            [
                'category' => '🔍 Panduan Supervisor',
                'title' => 'Validasi & Penolakan Usulan',
                'slug' => 'validasi-dan-penolakan-usulan',
                'icon' => '✅',
                'order' => 7,
                'content' => <<<'HTML'
<h2>Proses Validasi</h2>
<p>Untuk memvalidasi usulan yang telah sesuai:</p>
<ol class="list-decimal pl-5 space-y-2 my-4 text-sm text-gray-700">
    <li>Klik link dokumen PDF untuk memeriksa kelengkapan lampiran usulan.</li>
    <li>Pastikan volume, satuan, dan harga satuan telah sesuai standar biaya.</li>
    <li>Klik tombol <strong>Validasi</strong> pada baris usulan.</li>
    <li>Status usulan akan berubah menjadi <strong>Valid</strong> dan terkunci dari perubahan oleh operator.</li>
</ol>

<h2>Proses Penolakan (Reject)</h2>
<p>Jika usulan membutuhkan perbaikan:</p>
<ol class="list-decimal pl-5 space-y-2 my-4 text-sm text-gray-700">
    <li>Klik tombol <strong>Tolak</strong> pada baris usulan.</li>
    <li>Tuliskan alasan penolakan secara jelas pada kotak dialog yang muncul (misal: <em>"Mohon lampirkan rincian spek teknis terbaru"</em>).</li>
    <li>Klik <strong>Kirim Penolakan</strong>.</li>
    <li>Status usulan berubah menjadi <strong>Tolak</strong> dan operator dapat melakukan revisi.</li>
</ol>
HTML
            ],

            // Category: 👑 Panduan Administrator
            [
                'category' => '👑 Panduan Administrator',
                'title' => 'Penetapan Pagu Per Rekening',
                'slug' => 'penetapan-pagu-per-rekening',
                'icon' => '💰',
                'order' => 8,
                'content' => <<<'HTML'
<h2>Mekanisme Penetapan Pagu</h2>
<p>Administrator menetapkan pagu nominal anggaran untuk setiap nomor rekening belanja melalui menu <strong>RBA Headers &gt; Pagu</strong>.</p>

<div class="admonition admonition-danger">
    <div class="admonition-title">⛔ Syarat Wajib Penetapan Pagu</div>
    <div class="admonition-content">
        Administrator <strong>tidak dapat</strong> menyimpan penetapan pagu pada nomor rekening yang masih memiliki usulan belanja belum divalidasi oleh Supervisor. Seluruh usulan pada rekening tersebut wajib berstatus Valid terlebih dahulu.
    </div>
</div>

<h2>Fitur Penetapan Pagu Cepat (Asynchronous)</h2>
<ul class="list-disc pl-5 space-y-2 my-4 text-sm text-gray-700">
    <li><strong>Pencarian Cepat:</strong> Gunakan kotak pencarian di kanan atas tabel untuk menyaring kode rekening secara instan.</li>
    <li><strong>Simpan Instan:</strong> Klik tombol 💾 Simpan. Nominal pagu tersimpan langsung tanpa reload halaman.</li>
    <li><strong>Batal Pagu:</strong> Klik tombol <strong>Batal</strong> untuk membatalkan penetapan pagu rekening tersebut.</li>
    <li><strong>Real-time Stats:</strong> Ringkasan kartu statistik di bagian atas otomatis ter-update seketika.</li>
</ul>
HTML
            ],
            [
                'category' => '👑 Panduan Administrator',
                'title' => 'Log Data & Audit Trail',
                'slug' => 'log-data-dan-audit-trail',
                'icon' => '📋',
                'order' => 9,
                'content' => <<<'HTML'
<h2>Pencatatan Riwayat Transaksi (Audit Logging)</h2>
<p>Aplikasi RBA RSUD Kardinah mencatat secara otomatis setiap aktivitas manipulasi data (Create, Update, Delete) yang dilakukan oleh seluruh pengguna di semua level.</p>

<h2>Mengakses Menu Log Data</h2>
<p>Administrator dapat membuka menu <strong>Log Data</strong> pada navigasi utama untuk melihat:</p>
<ul class="list-disc pl-5 space-y-2 my-4 text-sm text-gray-700">
    <li>Nama pengguna dan Role pelaku transaksi.</li>
    <li>Jenis aksi (CREATED, UPDATED, DELETED).</li>
    <li>Model / Entitas database yang dimodifikasi.</li>
    <li>Alamat IP dan User-Agent perangkat.</li>
    <li>Perubahan nilai lama (*Old Values*) dan nilai baru (*New Values*).</li>
</ul>
HTML
            ],
            [
                'category' => '👑 Panduan Administrator',
                'title' => 'Manajemen Dokumentasi & Versi',
                'slug' => 'manajemen-dokumentasi-dan-versi',
                'icon' => '⚙️',
                'order' => 10,
                'content' => <<<'HTML'
<h2>Pengelolaan Buku Panduan oleh Administrator</h2>
<p>Administrator memiliki wewenang penuh untuk memperbarui isi buku panduan web (HTML) maupun mengunggah file panduan PDF edisi terbaru melalui panel <strong>Kelola Dokumentasi</strong> (<code>/admin/documentation</code>).</p>

<h2>Fitur Pengelolaan:</h2>
<ul class="list-disc pl-5 space-y-2 my-4 text-sm text-gray-700">
    <li><strong>Manajemen Versi:</strong> Membuat nomor versi baru (misal: v1.1.0, v2.0.0), catatan rilis (*changelog*), dan mengaktifkan versi tertentu sebagai versi live.</li>
    <li><strong>Manajemen Artikel:</strong> Menambah bab artikel baru, mengedit isi teks HTML/Markdown, mengubah urutan kategori, dan mengganti ikon topik.</li>
    <li><strong>Upload PDF:</strong> Mengunggah berkas PDF edisi revisi terbaru untuk dapat diunduh oleh pengguna.</li>
</ul>
HTML
            ],

            // Category: 💡 FAQ & Bantuan
            [
                'category' => '💡 FAQ & Bantuan',
                'title' => 'Pertanyaan Umum & Troubleshooting',
                'slug' => 'pertanyaan-umum-dan-troubleshooting',
                'icon' => '❓',
                'order' => 11,
                'content' => <<<'HTML'
<h2>Pertanyaan yang Sering Diajukan (FAQ)</h2>

<div class="my-4 space-y-4">
    <div class="p-4 rounded-xl border border-gray-200 bg-gray-50">
        <h4 class="font-bold text-sm text-gray-900">Q: Mengapa saya tidak bisa menginput rincian belanja?</h4>
        <p class="text-xs text-gray-600 mt-1 leading-relaxed">
            A: Pastikan Anda telah mengisi narasi <strong>Latar Belakang Unit</strong> terlebih dahulu. Selain itu, periksa apakah nomor rekening tersebut sudah ditetapkan pagunya oleh Administrator (nomor rekening yang sudah ditetapkan pagu terkunci untuk penambahan usulan baru).
        </p>
    </div>

    <div class="p-4 rounded-xl border border-gray-200 bg-gray-50">
        <h4 class="font-bold text-sm text-gray-900">Q: Mengapa tombol Edit dan Hapus hilang setelah usulan saya divalidasi?</h4>
        <p class="text-xs text-gray-600 mt-1 leading-relaxed">
            A: Usulan yang telah divalidasi oleh Supervisor terkunci otomatis demi integritas data anggaran. Jika terdapat perubahan mendesak, silakan hubungi Supervisor atau Administrator.
        </p>
    </div>

    <div class="p-4 rounded-xl border border-gray-200 bg-gray-50">
        <h4 class="font-bold text-sm text-gray-900">Q: Bagaimana cara mencari topik panduan tertentu dengan cepat?</h4>
        <p class="text-xs text-gray-600 mt-1 leading-relaxed">
            A: Tekan tombol kombinasi keyboard <kbd class="px-2 py-0.5 rounded bg-white border border-gray-300 shadow-sm text-[11px] font-mono">Ctrl + K</kbd> untuk membuka kotak pencarian instan di seluruh artikel dokumentasi.
        </p>
    </div>
</div>
HTML
            ],
        ];

        foreach ($articles as $art) {
            DocumentationArticle::updateOrCreate(
                [
                    'documentation_version_id' => $htmlVersion->id,
                    'slug' => $art['slug'],
                ],
                [
                    'category' => $art['category'],
                    'title' => $art['title'],
                    'icon' => $art['icon'],
                    'order' => $art['order'],
                    'content' => $art['content'],
                ]
            );
        }
    }
}
