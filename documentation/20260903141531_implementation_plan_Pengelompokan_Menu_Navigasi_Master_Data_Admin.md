# Implementation Plan - Pengelompokan Menu Navigasi "Master Data" pada Antarmuka Administrator

Menata dan merampingkan navigasi utama (*navbar*) Administrator pada file [navigation.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/layouts/navigation.blade.php) dengan mengelompokkan menu data referensi induk (**Units, Users, Kelompok Belanja, Nomor Rekening, dan Periode**) ke dalam satu kelompok menu dropdown bernama **"Master Data"**, sementara menu operasional lainnya (**Dashboard, RBA Headers, Log Data, dan Dokumentasi**) tetap ditampilkan secara mandiri.

---

## User Review Required

> [!IMPORTANT]
> **Struktur & Komposisi Menu Navigasi Administrator:**
> 1. **Menu yang Dikelompokkan ke dalam Dropdown "Master Data":**
>    - 🏢 **Units** (`admin.units.index`)
>    - 👥 **Users** (`admin.users.index`)
>    - 📁 **Kelompok Belanja** (`admin.kelompok-belanja.index`)
>    - 💳 **Nomor Rekening** (`admin.account-codes.index`)
>    - 📅 **Periode** (`admin.periods.index`)
> 2. **Menu yang Tetap Berdiri Sendiri (Tidak Dikelompokkan):**
>    - 📊 **Dashboard** (`dashboard`)
>    - 📑 **RBA Headers** (`admin.headers.index`)
>    - 📜 **Log Data** (`admin.logs.index`)
>    - 📖 **Dokumentasi** (`documentation.index`)
> 3. **Indikator Aktif (Active State Highlight):**
>    - Jika Administrator sedang membuka salah satu halaman di dalam kelompok Master Data (misal sedang di menu Nomor Rekening atau Periode), tombol menu trigger **"Master Data"** akan otomatis menyala aktif (garis bawah indigo aktif & teks lebih tebal) sehingga konteks lokasi navigasi tetap jelas bagi pengguna.
> 4. **Responsivitas Perangkat Mobile:**
>    - Pada tampilan mobile (*hamburger menu*), kelompok "Master Data" akan disajikan dalam bentuk menu accordion/collapsible atau sub-grup yang rapi, sehingga tidak memadati layar ponsel.

---

## Proposed Changes

### View Layer

#### [MODIFY] [navigation.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/layouts/navigation.blade.php)
1. **Desktop Navigation (`sm:flex`):**
   - Mengganti deretan 5 menu horizontal (`Units`, `Users`, `Kelompok Belanja`, `Nomor Rekening`, `Periode`) menjadi satu dropdown menu terpadu:
     ```blade
     <div class="hidden sm:flex sm:items-center">
         <x-dropdown align="left" width="56">
             <x-slot name="trigger">
                 <button class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('admin.units.*', 'admin.users.*', 'admin.kelompok-belanja.*', 'admin.account-codes.*', 'admin.periods.*') ? 'border-indigo-500 text-gray-900 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium leading-5 transition duration-150 ease-in-out h-16 focus:outline-none">
                     <span>{{ __('Master Data') }}</span>
                     <div class="ms-1.5 text-gray-400">
                         <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                             <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                         </svg>
                     </div>
                 </button>
             </x-slot>

             <x-slot name="content">
                 <x-dropdown-link :href="route('admin.units.index')" class="{{ request()->routeIs('admin.units.*') ? 'bg-indigo-50 text-indigo-700 font-bold' : '' }}">
                     🏢 {{ __('Units') }}
                 </x-dropdown-link>
                 <x-dropdown-link :href="route('admin.users.index')" class="{{ request()->routeIs('admin.users.*') ? 'bg-indigo-50 text-indigo-700 font-bold' : '' }}">
                     👥 {{ __('Users') }}
                 </x-dropdown-link>
                 <x-dropdown-link :href="route('admin.kelompok-belanja.index')" class="{{ request()->routeIs('admin.kelompok-belanja.*') ? 'bg-indigo-50 text-indigo-700 font-bold' : '' }}">
                     📁 {{ __('Kelompok Belanja') }}
                 </x-dropdown-link>
                 <x-dropdown-link :href="route('admin.account-codes.index')" class="{{ request()->routeIs('admin.account-codes.*') ? 'bg-indigo-50 text-indigo-700 font-bold' : '' }}">
                     💳 {{ __('Nomor Rekening') }}
                 </x-dropdown-link>
                 <x-dropdown-link :href="route('admin.periods.index')" class="{{ request()->routeIs('admin.periods.*') ? 'bg-indigo-50 text-indigo-700 font-bold' : '' }}">
                     📅 {{ __('Periode') }}
                 </x-dropdown-link>
             </x-slot>
         </x-dropdown>
     </div>
     ```
   - Mempertahankan menu `Dashboard`, `RBA Headers`, `Log Data`, dan `Dokumentasi` langsung di navbar tanpa perubahan.

2. **Mobile Responsive Navigation (`sm:hidden`):**
   - Mengelompokkan menu-menu tersebut ke dalam collapsible section "Master Data" menggunakan Alpine.js (`x-data="{ masterDataOpen: ... }"`) yang terbuka otomatis jika rute aktif berada di dalam kelompok Master Data.

---

### Automated Tests Layer

#### [NEW] [AdminNavigationTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/AdminNavigationTest.php)
- Menulis pengujian otomatis untuk memvalidasi:
  1. `test_admin_sees_master_data_dropdown_in_navigation()`: Memverifikasi keberadaan teks "Master Data" dan link ke Units, Users, Kelompok Belanja, Nomor Rekening, dan Periode.
  2. `test_master_data_dropdown_shows_active_state_when_sub_route_is_accessed()`: Memverifikasi tombol Master Data berstatus aktif saat rute anak (misal `admin.periods.index` atau `admin.account-codes.index`) sedang dibuka.
  3. `test_other_menus_remain_directly_visible_in_navigation()`: Memverifikasi `Dashboard`, `RBA Headers`, `Log Data`, dan `Dokumentasi` tetap ada dan dapat diakses.

---

## Verification Plan

### Automated Tests
1. Menjalankan test suite navigasi admin:
   ```powershell
   php artisan test --filter=AdminNavigationTest
   ```
2. Menjalankan test suite dashboard & management admin yang sudah ada:
   ```powershell
   php artisan test --filter=AdminDashboardTest
   ```
3. Menjalankan seluruh test suite aplikasi untuk memastikan zero regression:
   ```powershell
   php artisan test
   ```
4. Menjalankan build frontend dengan Bun:
   ```powershell
   bun run build
   ```

### Manual Verification
1. Login sebagai **Administrator**.
2. Perhatikan navbar atas di mode Desktop:
   - Pastikan menu terlihat lebih ringkas dan bersih.
   - Menu yang muncul langsung di navbar: **Dashboard**, **Master Data ▼**, **RBA Headers**, **Log Data**, dan **📖 Dokumentasi**.
3. Klik tombol **Master Data ▼**:
   - Pastikan menu dropdown muncul dengan item:
     - 🏢 Units
     - 👥 Users
     - 📁 Kelompok Belanja
     - 💳 Nomor Rekening
     - 📅 Periode
4. Klik salah satu item, misal **Nomor Rekening**:
   - Halaman nomor rekening terbuka dengan benar.
   - Tombol **Master Data** di navbar tetap menyala aktif (indikator visual aktif).
5. Buka tampilan mobile (responsif):
   - Klik tombol hamburger menu.
   - Pastikan kelompok Master Data tersaji secara rapi dan dapat dibuka/tutup dengan baik.
