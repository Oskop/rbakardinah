# Implementation Plan - Add `kode` to [KelompokBelanja](file:///c:/Users/PC12/Project/rbakardinah/app/Models/KelompokBelanja.php#7-16)

The goal is to update the [KelompokBelanja](file:///c:/Users/PC12/Project/rbakardinah/app/Models/KelompokBelanja.php#7-16) (Expense Group) master data to include a `kode` column which serves as a prefix for [AccountCode](file:///c:/Users/PC12/Project/rbakardinah/app/Models/AccountCode.php#7-16). We will also seed the database with the three specific groups provided by the user.

## Proposed Changes

### Database & Models

#### [MODIFY] [2026_02_25_053510_create_kelompok_belanjas_table.php](file:///c:/Users/PC12/Project/rbakardinah/database/migrations/2026_02_25_053510_create_kelompok_belanjas_table.php)
- Add `$table->string('kode')->unique()->after('id');` to the [up](file:///c:/Users/PC12/Project/rbakardinah/database/migrations/2026_02_25_053510_create_kelompok_belanjas_table.php#8-19) method.

#### [MODIFY] [KelompokBelanja.php](file:///c:/Users/PC12/Project/rbakardinah/app/Models/KelompokBelanja.php)
- Add `'kode'` to the `$fillable` array.

#### [MODIFY] [KelompokBelanjaSeeder.php](file:///c:/Users/PC12/Project/rbakardinah/database/seeders/KelompokBelanjaSeeder.php)
- Update the seeder to include the `kode` and correct `name` for the three groups:
    - `5.1.01`: Belanja Pegawai
    - `5.1.02`: Belanja Barang dan Jasa
    - `5.1.03`: Belanja Modal

#### [MODIFY] [AccountCodeSeeder.php](file:///c:/Users/PC12/Project/rbakardinah/database/seeders/AccountCodeSeeder.php)
- Update standard names (e.g., "Belanja Barang & Jasa" to "Belanja Barang dan Jasa") to match the new seeder data.

## Verification Plan

### Automated Tests
- Run `php artisan migrate:fresh --seed` to ensure the schema and data are correctly applied.
- Run a simple tinker command or query to verify the data:
  ```powershell
  php artisan tinker --execute="print_r(App\Models\KelompokBelanja::all()->toArray())"
  ```

### Manual Verification
- Check the `kelompok_belanjas` table in the database to confirm the `kode` column exists and contains the correct values.
