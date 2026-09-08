# Rencana Implementasi: Peningkatan UI & UX Kolom Aksi & Modal Upload Revisi PDF Operator

Peningkatan antarmuka (UI) dan pengalaman pengguna (UX) pada tabel rincian belanja operator di halaman penyerahan RBA (`/operator/submissions/{id}`). Perbaikan difokuskan pada penyederhanaan tombol aksi menjadi icon buttons dan menghilangkan input file mentah di tabel dengan menghadirkan **Modal Dialog Unggah Revisi PDF** yang elegan dan tidak ambigu.

---

## 1. Analisis Masalah UI/UX & Desain Solusi

### A. Masalah pada Tampilan Saat Ini
1. **Ambiguitas & Kekacauan Visual (*Cluttered UI*)**:
   - Pada kolom "Aksi" tabel rincian belanja, saat ini terdapat elemen input file mentah bawaan browser (`<input type="file" class="w-24">`) bersanding dengan tombol teks "Revisi" atau "Upload".
   - Browser menampilkan tombol default *"Choose File"* / *"No file chosen"* yang terpotong di dalam cell tabel yang sempit.
   - Operator mengalami kebingungan (*ambiguity*): apakah tombol "Revisi" berfungsi untuk merevisi nominal usulan atau merevisi dokumen lampiran PDF, dan mengapa tombol "Edit" terpisah di atasnya.
2. **Tombol Teks Memakan Ruang Horizontal**:
   - Tombol teks `"Edit"` dan `"Hapus"` memakan ruang kolom aksi tabel, membuat tabel melebar secara horizontal dan kurang proporsional.

### B. Solusi Desain yang Diusulkan
1. **Transformasi Tombol Edit, Hapus, dan Ajukan Menjadi Icon Buttons**:
   - **Edit**: Icon pensil (*Pencil*) dengan background lembut, border halus, dan tooltip (*title*) informatif: `"Edit Rincian Belanja"`.
   - **Hapus**: Icon tempat sampah (*Trash*) berwarna rose/red dengan dialog konfirmasi dan tooltip: `"Hapus Rincian Belanja"`.
   - **Ajukan**: Icon kirim (*Paper Airplane*) berwarna emerald/green dengan tooltip: `"Ajukan ke Supervisor"`.
2. **Tombol Aksi Unggah Revisi PDF (Menggantikan Input File Mentah)**:
   - Menghilangkan elemen `<input type="file">` dari dalam baris tabel rincian belanja.
   - Menggantinya dengan **Tombol Trigger Modal**:
     - **Kondisi Normal (Unggah Versi PDF Baru)**: Icon dokumen panah atas (*Document Arrow Up / Upload*) berwarna sky/slate dengan tooltip: `"Unggah Revisi Dokumen PDF"`.
     - **Kondisi Khusus (Over Pagu & Wajib Upload Revisi)**: Tombol badges beraksen khusus (*amber/orange*) dengan teks mini dan icon upload:
       `📤 Upload PDF Penyesuaian`
       sehingga operator dengan jelas mengetahui rincian mana yang membutuhkan unggahan dokumen penyesuaian pagu.
3. **Modal Dialog Interaktif "Unggah Revisi Dokumen PDF"**:
   - Ketika operator mengklik tombol revisi PDF, sebuah modal dialog fokus akan terbuka:
     - **Header**: Judul jelas *"Unggah Revisi Dokumen PDF Lampiran"*.
     - **Konteks Rincian Belanja**: Menampilkan ringkasan kode rekening, deskripsi rincian belanja, nominal usulan, dan versi PDF saat ini (misal: *Versi Saat Ini: V1*).
     - **Pesan Panduan Khusus**: Jika rincian melebihi pagu yang ditetapkan, modal menampilkan instruksi peringatan yang ramah (*"Nominal rincian ini melebihi pagu. Silakan unggah dokumen PDF yang telah disesuaikan agar dapat diajukan ke Supervisor."*).
     - **Area Unggah File Modern**: Dropzone file picker dengan indikator format PDF (maks. 10MB) dan penampil nama file yang dipilih secara real-time.
     - **Aksi**: Tombol *"Batal"* dan tombol *"Simpan & Unggah Dokumen PDF"*.

---

## 2. Rincian Perubahan Berkas (*Proposed Changes*)

### Frontend Views

#### [MODIFY] [resources/views/operator/submissions/show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/submissions/show.blade.php)
1. **Penambahan State Alpine.js**:
   - Tambahkan state modal:
     - `uploadModalOpen`: boolean (status buka/tutup modal).
     - `uploadTargetDetail`: objek data rincian aktif (`id`, `accountName`, `description`, `nominal`, `currentVersion`, `uploadUrl`, `isExceeding`, `hasRevision`).
     - `selectedFileName`: nama berkas PDF yang dipilih.
     - Method `openUploadModal(detail)` dan `closeUploadModal()`.
2. **Pembaruan Kolom Aksi Tabel (`<td>`)**:
   - Ganti tombol teks `"Edit"`, `"Hapus"`, `"Ajukan"` menjadi Icon Buttons modern dengan tooltip yang presisi.
   - Hapus form inline `<input type="file">` dari cell tabel.
   - Tambahkan tombol icon trigger modal `"Unggah Revisi PDF"` dan tombol peringatan *"Upload PDF Penyesuaian"* jika over pagu.
3. **Penyisipan Markup Modal Dialog "Unggah Revisi Dokumen PDF"**:
   - Letakkan template modal dialog di bagian bawah komponen tabel yang terikat dengan state Alpine.js di atas.

---

## 3. Rencana Verifikasi (*Verification Plan*)

### Automated Tests
- Menjalankan pengujian fitur rincian belanja operator:
  `php artisan test --filter=RbaDetailTest`
  `php artisan test --filter=RbaDetailFeaturesTest`
- Menjalankan seluruh test suite aplikasi untuk memastikan tidak ada regresi:
  `php artisan test`
- Menjalankan kompilasi aset frontend Vite:
  `bun run build`

### Manual Verification
1. Buka halaman rincian belanja usulan operator: `/operator/submissions/{id}`.
2. Periksa kolom Aksi:
   - Tombol Edit & Hapus tampil rapi berupa Icon Buttons dengan hover effect dan tooltip.
   - Tidak ada lagi input file mentah browser *"Choose File"* yang sempit atau terpotong di tabel.
3. Klik tombol Icon Unggah Dokumen PDF pada salah satu rincian:
   - Modal Dialog "Unggah Revisi Dokumen PDF Lampiran" muncul di tengah layar dengan backdrop blur.
   - Rincian rekening, deskripsi usulan, dan versi dokumen saat ini ditampilkan secara akurat.
4. Pilih file PDF dan klik "Simpan & Unggah Dokumen PDF":
   - Berkas terunggah sukses, versi PDF bertambah (misal V1 -> V2), dan status usulan kembali menjadi Draft.
5. Pada rincian yang melebihi pagu (*Over Pagu*):
   - Verifikasi tombol aksen oranye *"Upload PDF Penyesuaian"* tampil mencolok dan membuka modal dengan alert informasi penyesuaian pagu.

---

## 4. Lokasi Penyimpanan Dokumentasi
Rencana implementasi ini disimpan di:
- `documentation/20260908073500_implementation_plan_Peningkatan_UIUX_Kolom_Aksi_Dan_Modal_Upload_Revisi_PDF_Operator.md`
