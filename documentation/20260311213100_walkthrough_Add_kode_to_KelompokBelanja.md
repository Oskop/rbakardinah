# Walkthrough - Add `kode` to [KelompokBelanja](file:///c:/Users/PC12/Project/rbakardinah/app/Models/KelompokBelanja.php#7-16)

I have updated the [KelompokBelanja](file:///c:/Users/PC12/Project/rbakardinah/app/Models/KelompokBelanja.php#7-16) master data to include a `kode` column and seeded the initial three groups as requested.

## Changes Made

### Database & Models

#### [2026_02_25_053510_create_kelompok_belanjas_table.php](file:///c:/Users/PC12/Project/rbakardinah/database/migrations/2026_02_25_053510_create_kelompok_belanjas_table.php)
- Added `kode` column with a unique constraint.

#### [KelompokBelanja.php](file:///c:/Users/PC12/Project/rbakardinah/app/Models/KelompokBelanja.php)
- Added `kode` to the fillable attributes.

### Seeders

#### [KelompokBelanjaSeeder.php](file:///c:/Users/PC12/Project/rbakardinah/database/seeders/KelompokBelanjaSeeder.php)
- Updated to seed the three required groups:
  - `5.1.01`: Belanja Pegawai
  - `5.1.02`: Belanja Barang dan Jasa
  - `5.1.03`: Belanja Modal

#### [AccountCodeSeeder.php](file:///c:/Users/PC12/Project/rbakardinah/database/seeders/AccountCodeSeeder.php)
- Updated the group name lookup from "Belanja Barang & Jasa" to "Belanja Barang dan Jasa" to maintain consistency.

## Verification Results

### Automated Tests
- Successfully ran `php artisan migrate:fresh --seed`.
- Verified the data using Tinker:
```php
Array
(
    [0] => Array
        (
            [id] => 1
            [kode] => 5.1.01
            [name] => Belanja Pegawai
        )

    [1] => Array
        (
            [id] => 2
            [kode] => 5.1.02
            [name] => Belanja Barang dan Jasa
        )

    [2] => Array
        (
            [id] => 3
            [kode] => 5.1.03
            [name] => Belanja Modal
        )
)
```
