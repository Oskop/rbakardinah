# Implementation Plan: Perbaikan Tuntas `Uncaught ReferenceError: openDropdown is not defined` & `search is not defined` pada Formulir RKBMD

Dokumen ini memuat analisis akar penyebab teknis dari error JavaScript yang muncul di konsol peramban (`ReferenceError: openDropdown is not defined` dan `ReferenceError: search is not defined`), serta rencana perbaikan tuntas dan bersih agar antarmuka combobox berjalan stabil tanpa ada benturan sintaks atau *bug* sampingan.

---

## 1. Analisis Akar Masalah (Root Cause Analysis)

### Mengapa `openDropdown` dan `search` menjadi `not defined`?

Penyebabnya adalah **Premature HTML Attribute Termination (Benturan Tanda Kutip HTML)** pada baris deklarasi `x-data`:

```mermaid
flowchart TD
    A[Blade merender atribut HTML: x-data='{ ... }'] --> B[Parser HTML membaca atribut x-data dari tanda petik dua pembuka]
    B --> C[Di baris 238 terdapat: container.querySelector(`data-item-index=QUOTE${this.highlightedIndex}QUOTE`)]
    C --> D[Karakter QUOTE menutup atribut x-data secara prematur di tengah kode JS!]
    D --> E[Atribut x-data terpotong & sisa kode menjadi teks HTML rusak]
    E --> F[Alpine.js gagal mengevaluasi x-data: Syntax Error / Incomplete Object]
    F --> G[Komponen combobox tidak terinisialisasi pada elemen div]
    G --> H[Event @focus='openDropdown()' dan @input='search()' mencari fungsi di window global]
    H --> I[ERROR: Uncaught ReferenceError: openDropdown is not defined & search is not defined!]
```

### Bukti Kode:
Pada pembaruan sebelumnya:
```javascript
// Baris di dalam atribut x-data="..."
const itemEl = container.querySelector(`[data-item-index="${this.highlightedIndex}"]`);
```
Karena atribut HTML didefinisikan menggunakan tanda kutip dua:
`<div class="md:col-span-6" x-data="{ ... }">`
Karakter kutip dua `"` pada `"${this.highlightedIndex}"` **dianggap oleh peramban sebagai penutup atribut `x-data`**!

Akibatnya:
1. String JavaScript di dalam `x-data` terpotong di tengah jalan.
2. Alpine.js mendeteksi *Syntax Error* saat mem-parse atribut `x-data`, sehingga objek state dan method (`openDropdown`, `closeDropdown`, `search`, `selectBarang`, dll.) **gagal diinstansiasi**.
3. Ketika pengguna memfokuskan kursor ke input atau mengetik, peramban mengevaluasi `@focus="openDropdown()"` dan `@input.debounce.250ms="search()"` ke level global `window`, di mana method tersebut tidak ada, sehingga melempar:
   - `Uncaught ReferenceError: openDropdown is not defined`
   - `Uncaught ReferenceError: search is not defined`

---

## 2. Solusi Arsitektur yang Benar & Robust (Alpine.js Best Practice)

Memasukkan lebih dari 100 baris logika JavaScript kompleks (fungsi async, fetch, template literals, selector) langsung ke dalam atribut string HTML `x-data="..."` adalah antipattern yang sangat rentan terhadap *escaping* dan *quote clashing*.

### Pendekatan yang Benar:
Ekstrak seluruh logika combobox ke dalam **fungsi JavaScript modular terpisah**:
```javascript
function rkbmdBarangCombobox(item, masterBarangs, searchUrl) {
    return {
        open: false,
        query: '',
        loading: false,
        results: [],
        highlightedIndex: -1,
        init() { ... },
        openDropdown() { ... },
        closeDropdown() { ... },
        async search() { ... },
        selectBarang(b) { ... },
        resetBarang() { ... },
        highlightNext() { ... },
        highlightPrev() { ... },
        selectHighlighted() { ... },
        scrollToHighlighted() { ... }
    };
}
```

Dan pada elemen Blade, cukup panggil secara ringkas dan bersih:
```html
<div class="md:col-span-6" x-data="rkbmdBarangCombobox(item, masterBarangs, searchUrl)">
```

### Keunggulan Pendekatan Ini:
1. **Bebas 100% dari Masalah Benturan Kutip HTML**: Kode JavaScript berada di blok `<script>` native, bukan di dalam string atribut HTML.
2. **Kinerja Render Jauh Lebih Cepat**: Peramban tidak perlu mengurai string HTML 100 baris berulang kali untuk setiap baris barang dinamis.
3. **Kode Blade Bersih & Rapi**: Tampilan HTML menjadi ringkas, mudah dibaca, dan mudah dipelihara.
4. **Fungsi Scroll Tetap Sempurna**: Seluruh perbaikan sebelumnya (`@mousedown.stop`, `@click.stop`, `@click.outside` pada parent, `max-h-72`, `overscroll-contain`, dan `custom-scrollbar`) tetap aktif 100%.

---

## 3. Rincian Langkah Implementasi

### Langkah 1: Pasang Fungsi `rkbmdBarangCombobox` di `<script>`
Pada [resources/views/operator/rkbmd/create.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/create.blade.php) dan [resources/views/operator/rkbmd/edit.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/edit.blade.php):
Tambahkan blok skrip modular:
```html
@push('scripts')
<script>
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
</script>
@endpush
```

### Langkah 2: Sederhanakan Markup Blade
Ganti tag pembuka combobox yang sebelumnya memuat 100 baris kode JS menjadi:
```html
<!-- Barang BMD Async Combobox -->
<div class="md:col-span-6" x-data="rkbmdBarangCombobox(item, masterBarangs, searchUrl)">
    <label class="block text-[11px] font-bold text-slate-700 mb-1">
        Nama / Kode Barang BMD Permendagri 108 <span class="text-rose-500">*</span>
    </label>
    ...
```

---

## 4. Rencana Pengujian & Verifikasi

1. **Pemeriksaan Konsol Browser (Zero Error)**:
   - Memastikan tidak ada lagi pesan error `ReferenceError: openDropdown is not defined` maupun `search is not defined`.
2. **Uji Pengetikan & Debounce Search**:
   - Memasukkan kata kunci pencarian pada form tambah dan form edit.
   - Memastikan dropdown menampilkan data hasil pencarian dengan lancar.
3. **Uji Pengguliran (Scroll) & Navigasi**:
   - Memastikan scrollbar dapat di-drag, mouse wheel berfungsi mulus, dan panah keyboard dapat digunakan tanpa kendala.
4. **Automated Regression Test**:
   - Menjalankan `php artisan test --filter="RkbmdTest|PermendagriMasterBarangTest"`.
   - Seluruh 33 pengujian wajib lulus 100%.

---

## 📁 Dokumen Rencana
Dokumen rencana ini disimpan di:
- [`documentation/20261024081000_implementation_plan_Perbaikan_ReferenceError_Combobox_RKBMD.md`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/documentation/20261024081000_implementation_plan_Perbaikan_ReferenceError_Combobox_RKBMD.md)
- Artifact: [implementation_plan.md](file:///C:/Users/PDETUF/.gemini/antigravity-ide/brain/a53b4c36-63b2-4f67-9eb9-e58685591751/implementation_plan.md)
