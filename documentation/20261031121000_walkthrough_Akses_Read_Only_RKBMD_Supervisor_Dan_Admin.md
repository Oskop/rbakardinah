# Walkthrough: Hak Akses Read-Only Fitur RKBMD untuk Supervisor dan Administrator

## 1. Ringkasan Eksekutif & Latar Belakang

Fitur **RKBMD (Rencana Kebutuhan Barang Milik Daerah)** sebelumnya hanya dapat diakses secara tertutup oleh akun dengan role **Operator** (`role:Operator`). Pengguna meminta agar:
1. **User Administrator** dapat mengakses dan meninjau seluruh permohonan kebutuhan barang dari semua unit kerja se-Rumah Sakit (*hospital-wide*).
2. **User Supervisor** dapat mengakses dan meninjau permohonan kebutuhan barang yang berkaitan dengan unit kerjanya secara dua arah:
   - Permohonan yang **diajukan oleh** sub-unit di bawah unit kerjanya (*outbound*), serta
   - Permohonan yang **ditujukan ke** sub-unit / operator di bawah unit kerjanya (*inbound*).
3. **Hak Akses Mutlak Read-Only**: Baik Administrator maupun Supervisor **sama sekali tidak boleh** dapat membuat, mengedit, menghapus, membalas, maupun mengalihkan permohonan berkas RKBMD.

---

## 2. Cakupan Data (*Data Scoping*) Berdasarkan Role

| Role | Ruang Lingkup Data (*Query Scoping*) | Tampilan & Fitur di Antarmuka |
| :--- | :--- | :--- |
| **Administrator** | **Seluruh Rumah Sakit**: Seluruh berkas RKBMD dari semua unit dan sub-unit tanpa terkecuali. | - Tab *"Daftar Seluruh Permohonan RKBMD (Rumah Sakit)"*<br>- Filter dropdown khusus *"Unit Kerja Induk"*<br>- Kolom Asal Unit & Sub-Unit<br>- Badge *"Mode Peninjauan (Read-Only)"* |
| **Supervisor** | **Unit Kerja Terkait (Dua Arah)**:<br>1. Berkas dari sub-unitnya (`unit_id = supervisor.unit_id`)<br>2. Berkas ditujukan ke operatornya (`targetOperator.unit_id = supervisor.unit_id`) | - Tab *"Daftar Permohonan RKBMD Terkait Unit [Nama Unit]"*<br>- Badge visual pembeda arah:<br>&nbsp;&nbsp;• 📤 *Diajukan dari Sub-Unit Kita*<br>&nbsp;&nbsp;• 📥 *Ditujukan ke Operator Kita*<br>- Badge *"Mode Peninjauan (Read-Only)"* |
| **Operator** | **Operasional Mandiri**: Berkas yang diajukan sendiri, berkas masuk ke dirinya, dan berkas yang pernah dialihkannya. | - 3 Tab Operasional (*Permohonan Saya*, *Permohonan Masuk*, *Permohonan Dialihkan*)<br>- Tombol *"✍️ Buat Permohonan Baru"*<br>- Tombol *"Edit Permohonan"*, *"Tanggapi / Balas"*, *"Alihkan"* |

---

## 3. Arsitektur Proteksi Read-Only (Quad-Layer Security Guard)

Untuk menjamin tidak ada manipulasi data baik secara visual maupun via request langsung (cURL / Postman / DevTools):

1. **Lapisan 1: Route Middleware (`routes/web.php`)**:
   - Rute baca (`GET /rkbmd` dan `GET /rkbmd/{rkbmd}`) dibuka untuk `role:Operator,Supervisor,Administrator`.
   - Rute mutasi (`create`, `store`, `edit`, `update`, `destroy`, `reply`, `forward`, `updateReply`) tetap terkunci ketat di bawah `role:Operator`.
2. **Lapisan 2: Model Authorization Helpers (`RkbmdSubmission.php`)**:
   - `canEditSubmission(User $user)`: Mengembalikan `false` jika role bukan `Operator`.
   - `canBeManagedBy(User $user)`: Mengembalikan `false` jika role bukan `Operator`.
   - `canEditReply(User $user)`: Mengembalikan `false` jika role bukan `Operator`.
3. **Lapisan 3: Controller Defense Guard (`RkbmdController.php`)**:
   - Pada method `create()`, `store()`, `edit()`, `update()`, `reply()`, `forward()`: Diberikan guard `if (Auth::user()->role !== 'Operator') abort(403);`.
   - Pada method `show()`: Supervisor dibatasi hanya dapat melihat permohonan yang terkait dengan unitnya. Jika mencoba melihat permohonan unit lain, sistem melempar `403 Forbidden`.
4. **Lapisan 4: Tampilan Blade Views**:
   - Tombol *"Buat Permohonan Baru"* disembunyikan untuk non-operator.
   - Tombol *"Edit Permohonan"*, *"Tanggapi / Balas"*, *"Alihkan ke Pengusul Lain"*, dan *"Edit Balasan"* disembunyikan.
   - Ditampilkan banner informatif abu-abu *"Mode Peninjauan (Read-Only)"*.

---

## 4. Perubahan Berkas Utama

### 4.1. Routing & Otorisasi
- **[routes/web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)**:
  - Mendaftarkan rute baca `rkbmd.index` dan `rkbmd.show` serta alias `operator.rkbmd.index` dan `operator.rkbmd.show` untuk kompatibilitas penuh.
- **[app/Models/RkbmdSubmission.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RkbmdSubmission.php)**:
  - Mengunci helper method `canBeManagedBy`, `canEditReply`, dan `canEditSubmission` agar hanya berlaku untuk Operator.
- **[app/Http/Controllers/Operator/RkbmdController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/RkbmdController.php)**:
  - Menyesuaikan method `index()` dengan percabangan data untuk Administrator dan Supervisor.
  - Memperbarui method `show()` untuk mengizinkan peninjauan berkas bagi Administrator dan Supervisor unit terkait.
  - Menambahkan guard proteksi di awal `create()` dan `store()`.

### 4.2. Navigasi & Tampilan
- **[resources/views/layouts/navigation.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/layouts/navigation.blade.php)**:
  - Menambahkan tautan menu `📦 RKBMD` pada navbar desktop dan menu responsif mobile untuk Administrator dan Supervisor.
- **[resources/views/operator/rkbmd/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/index.blade.php)**:
  - Header adaptif berdasarkan role.
  - Filter Unit Kerja khusus Administrator.
  - Tabel khusus Administrator (`#table-admin-rkbmd`) dan tabel Supervisor (`#table-supervisor-rkbmd`) dengan badge dua arah (*Inbound* / *Outbound*).
  - Skrip DataTables diperbarui agar dapat menyaring tanggal, status, unit, dan pencarian cepat secara reaktif.
- **[resources/views/operator/rkbmd/show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/show.blade.php)**:
  - Banner *"Mode Peninjauan (Read-Only)"* untuk peninjau non-operator.
  - Tautan tombol *"Kembali"* diarahkan ke `route('rkbmd.index')`.

---

## 5. Hasil Verifikasi Pengujian Otomatis

Seluruh 9 skenario pengujian baru pada [`Tests\Feature\General\RkbmdReadOnlyAccessTest`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/General/RkbmdReadOnlyAccessTest.php) dan 28 pengujian pada [`Tests\Feature\Operator\RkbmdTest`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Operator/RkbmdTest.php) berjalan sukses:

| Kasus Uji | Deskripsi Pengujian | Hasil |
| :--- | :--- | :---: |
| `test_admin_can_view_rkbmd_index_and_see_all_hospital_submissions` | Memverifikasi Admin melihat seluruh permohonan lintas unit tanpa tombol buat | **PASSED (Green)** |
| `test_supervisor_can_view_rkbmd_index_and_see_submissions_originated_from_and_targeted_to_own_unit` | Memverifikasi Supervisor melihat berkas dua arah (outbound & inbound) | **PASSED (Green)** |
| `test_supervisor_cannot_view_unrelated_submission_in_show` | Memverifikasi Supervisor ditolak 403 saat mencoba melihat berkas unit lain | **PASSED (Green)** |
| `test_admin_and_supervisor_can_view_related_submission_show_page_read_only` | Memverifikasi tampilan detail show page berstatus read-only tanpa tombol aksi | **PASSED (Green)** |
| `test_admin_and_supervisor_cannot_access_create_form` | Memverifikasi route create ditolak 403 Forbidden | **PASSED (Green)** |
| `test_admin_and_supervisor_cannot_submit_store_request` | Memverifikasi route store ditolak 403 Forbidden | **PASSED (Green)** |
| `test_admin_and_supervisor_cannot_access_edit_form_or_submit_update` | Memverifikasi route edit/update ditolak 403 Forbidden | **PASSED (Green)** |
| `test_admin_and_supervisor_cannot_reply_or_forward_submission` | Memverifikasi route reply/forward ditolak 403 Forbidden | **PASSED (Green)** |
| `test_operator_retains_full_operational_access_without_regression` | Memverifikasi Operator tetap memiliki akses penuh operasional | **PASSED (Green)** |

---

## 6. Panduan Penggunaan Singkat

1. **Sebagai Administrator**:
   - Klik menu **📦 RKBMD** pada navbar atas.
   - Anda dapat memantau seluruh usulan kebutuhan barang, memilih filter unit kerja tertentu, serta mengklik tombol **Tinjau Berkas** untuk melihat rincian barang, dokumen PDF nota dinas, dan linimasa pengalihan/balasan.
2. **Sebagai Supervisor**:
   - Klik menu **📦 RKBMD** pada navbar atas.
   - Anda akan melihat daftar usulan kebutuhan barang terkait unit Anda. Perhatikan badge arah:
     - 📤 **Diajukan dari Sub-Unit Kita**: Usulan yang diajukan oleh sub-unit di bawah unit Anda kepada Operator Pengusul.
     - 📥 **Ditujukan ke Operator Kita**: Usulan dari unit lain yang ditujukan ke Operator Pengusul di unit Anda untuk ditelaah.
   - Klik **Tinjau Berkas** untuk memeriksa detail secara mendalam.

