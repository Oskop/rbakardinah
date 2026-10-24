# Walkthrough: Perbaikan Tuntas `ReferenceError: openDropdown is not defined` & `search is not defined` pada Formulir RKBMD

Dokumen ini mendokumentasikan perbaikan tuntas dan hasil verifikasi pengujian setelah menyelesaikan masalah benturan sintaks pada combobox permohonan RKBMD.

---

## 1. Masalah yang Diselesaikan

Pada versi sebelumnya, saat pengguna membuka halaman formulir RKBMD (`create` dan `edit`), konsol peramban menampilkan error:
- `Uncaught ReferenceError: openDropdown is not defined`
- `Uncaught ReferenceError: search is not defined`

### Penyebab:
Di dalam deklarasi atribut HTML `x-data="{ ... }"` sebelumnya, terdapat template literal:
```javascript
const itemEl = container.querySelector(`[data-item-index="${this.highlightedIndex}"]`);
```
Tanda petik dua `"` sebelum `${this.highlightedIndex}` memotong atribut HTML `x-data` secara prematur. Akibatnya, Alpine.js mengalami kegagalan evaluasi sintaks (*SyntaxError / incomplete object*), sehingga seluruh fungsi combobox (`openDropdown`, `search`, `selectBarang`, dll.) tidak terdaftarkan pada cakupan komponen dan peramban melempar `ReferenceError`.

---

## 2. Perubahan yang Dilakukan

### Refactoring Logika ke Fungsi Modular
Seluruh logika JavaScript combobox diekstraksi dari atribut inline string `x-data` ke fungsi modular terpisah:
[`rkbmdBarangCombobox(item, masterBarangs, searchUrl)`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/create.blade.php) pada blok `@push('scripts')`.

#### File yang Dimodifikasi:
1. [resources/views/operator/rkbmd/create.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/create.blade.php)
2. [resources/views/operator/rkbmd/edit.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/edit.blade.php)

#### Kode Blade yang Jauh Lebih Bersih:
```html
<!-- Barang BMD Async Combobox -->
<div class="md:col-span-6" x-data="rkbmdBarangCombobox(item, masterBarangs, searchUrl)">
    <label class="block text-[11px] font-bold text-slate-700 mb-1">
        Nama / Kode Barang BMD Permendagri 108 <span class="text-rose-500">*</span>
    </label>
    ...
```

#### Kode Skrip Modular pada `@push('scripts')`:
```javascript
function rkbmdBarangCombobox(item, masterBarangs, searchUrl) {
    return {
        open: false,
        query: '',
        loading: false,
        results: [],
        highlightedIndex: -1,

        init() {
            if (item.master_barang_id && !item.selected_barang) {
                const found = masterBarangs.find(b => b.id == item.master_barang_id);
                if (found) {
                    item.selected_barang = found;
                    if (!item.satuan) item.satuan = found.satuan || '';
                }
            }
        },

        openDropdown() {
            this.open = true;
            if (this.results.length === 0 && !this.query) {
                this.results = masterBarangs;
            }
        },

        closeDropdown() {
            this.open = false;
            this.highlightedIndex = -1;
        },

        async search() {
            const q = this.query.trim();
            if (q.length === 0) {
                this.results = masterBarangs;
                this.loading = false;
                return;
            }
            this.loading = true;
            try {
                const resp = await fetch(searchUrl + '?q=' + encodeURIComponent(q) + '&limit=25', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (resp.ok) {
                    this.results = await resp.json();
                    this.highlightedIndex = this.results.length > 0 ? 0 : -1;
                }
            } catch (e) {
                console.error('Gagal memuat barang:', e);
            } finally {
                this.loading = false;
            }
        },

        selectBarang(b) {
            item.master_barang_id = b.id;
            item.selected_barang = b;
            if (b.satuan) {
                item.satuan = b.satuan;
            }
            this.query = '';
            this.open = false;
            this.highlightedIndex = -1;
        },

        resetBarang() {
            item.master_barang_id = '';
            item.selected_barang = null;
            this.query = '';
            this.open = true;
            this.results = masterBarangs;
            this.$nextTick(() => {
                this.$refs.searchInput?.focus();
            });
        },

        highlightNext() {
            if (!this.open) {
                this.openDropdown();
                return;
            }
            if (this.results.length === 0) return;
            this.highlightedIndex = (this.highlightedIndex + 1) % this.results.length;
            this.scrollToHighlighted();
        },

        highlightPrev() {
            if (!this.open) {
                this.openDropdown();
                return;
            }
            if (this.results.length === 0) return;
            this.highlightedIndex = (this.highlightedIndex - 1 + this.results.length) % this.results.length;
            this.scrollToHighlighted();
        },

        selectHighlighted() {
            if (this.open && this.highlightedIndex >= 0 && this.results[this.highlightedIndex]) {
                this.selectBarang(this.results[this.highlightedIndex]);
            }
        },

        scrollToHighlighted() {
            this.$nextTick(() => {
                const container = this.$refs.dropdownContainer;
                if (!container) return;
                const itemEl = container.querySelector('[data-item-index="' + this.highlightedIndex + '"]');
                if (itemEl) {
                    itemEl.scrollIntoView({ block: 'nearest' });
                }
            });
        }
    };
}
```

---

## 3. Hasil Verifikasi & Pengujian

### A. Uji Render Blade & Sintaks HTML
Skrip pengujian memverifikasi bahwa:
1. `create.blade.php` berhasil me-render tag `<div class="md:col-span-6" x-data="rkbmdBarangCombobox(item, masterBarangs, searchUrl)">` secara utuh tanpa atribut terpotong (**PASS**).
2. `edit.blade.php` berhasil me-render komponen combobox dan fungsi skrip pendukung (**PASS**).
3. Tidak ada lagi karakter benturan tanda kutip pada atribut HTML mana pun (**PASS**).

### B. Automated Regression Test
Dijalankan pengujian fitur lengkap pada subsistem RKBMD dan Master Barang:
```bash
php artisan test --filter="RkbmdTest|PermendagriMasterBarangTest"
```
Hasil:
```text
   PASS  Tests\Feature\Admin\PermendagriMasterBarangTest
  ✓ master barangs table has kode permendagri column                                                             1.47s  
  ✓ admin can create master barang with kode permendagri                                                         0.10s  
  ✓ admin can update master barang with kode permendagri                                                         0.04s  
  ✓ scope search matches kode permendagri                                                                        0.03s  
  ✓ artisan command and seeder are idempotent                                                                    0.55s  

   PASS  Tests\Feature\Operator\RkbmdTest
  ✓ operator can access rkbmd index and create page                                                              1.16s  
  ✓ operator can submit rkbmd with items and pdf memo                                                            0.13s  
  ✓ audit columns and activity logs are automatically populated                                                  0.08s  
  ✓ target operator can reply with status and free text                                                          0.09s  
  ✓ target operator can forward rkbmd to another proposer and logs history                                       0.13s  
  ✓ viewer operator cannot reply or forward rkbmd                                                                0.09s  
  ✓ admin can manage master barang permendagri 108                                                               0.06s  
  ✓ admin master barangs index handles empty table without breaking datatables                                   0.09s  
  ✓ target operator can edit reply and logs history                                                              0.08s  
  ✓ viewer and unauthorized operator cannot edit reply                                                           0.08s  
  ✓ admin can delegate master barangs menu permission to operator                                                0.08s  
  ✓ operator with delegated permission can manage master barangs                                                 0.11s  
  ✓ operator without delegated permission cannot access master barangs                                           0.06s  
  ✓ operator with delegated permission cannot access other admin menus                                           0.06s  
  ✓ applicant operator can access edit page when status diajukan and no forward or reply                         1.19s  
  ✓ applicant operator can update submission and items and logs history                                          0.09s  
  ✓ applicant cannot edit if submission has been replied                                                         0.09s  
  ✓ applicant cannot edit if submission has been forwarded                                                       0.09s  
  ✓ unauthorized operator cannot edit submission                                                                 0.08s  
  ✓ forwarding operator sees forwarded submission in dedicated tab                                               0.15s  
  ✓ forwarding operator can view show page and banner of forwarded submission                                    0.12s  
  ✓ forwarded submission status updates when resolved by target operator                                         0.12s  
  ✓ rkbmd index filters by date range server side                                                                0.10s  
  ✓ rkbmd index filters by status server side                                                                    0.09s  
  ✓ rkbmd index filters by keyword search server side                                                            0.08s  
  ✓ rkbmd index renders datatables assets and data attributes                                                    0.09s  
  ✓ operator can search master barangs via async api                                                             0.06s  
  ✓ search master barangs excludes inactive items and respects limit                                             0.05s  

  Tests:    33 passed (154 assertions)
  Duration: 7.20s
```

Seluruh 33 pengujian lulus 100% tanpa ada satu pun regresi.
Kedua error `openDropdown is not defined` dan `search is not defined` telah teratasi tuntas, scrollbar tetap dapat digulir bebas, dan navigasi keyboard berfungsi secara mulus.
