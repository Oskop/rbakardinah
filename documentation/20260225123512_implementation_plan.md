# Implementation Plan - Account Code & Kelompok Belanja Refactoring

Refactor the `account_codes` table to link with a new `kelompok_belanjas` (Expense Group) master table. This follows the requirement to categorize account codes properly and fix the discrepancy between the code and database schema.

## Proposed Changes

### Database
#### [NEW] [Migration](file:///c:/Users/PC12/Project/rbakardinah/database/migrations/2026_02_25_000001_create_kelompok_belanjas_table.php)
- Create `kelompok_belanjas` table with `id` and `name`.

#### [NEW] [Migration](file:///c:/Users/PC12/Project/rbakardinah/database/migrations/2026_02_25_000002_add_kelompok_belanja_id_to_account_codes_table.php)
- Add `kelompok_belanja_id` foreign key to `account_codes`.

### Models
#### [NEW] [KelompokBelanja.php](file:///c:/Users/PC12/Project/rbakardinah/app/Models/KelompokBelanja.php)
- Create model for Kelompok Belanja.

#### [MODIFY] [AccountCode.php](file:///c:/Users/PC12/Project/rbakardinah/app/Models/AccountCode.php)
- Add `kelompok_belanja_id` to `$fillable`.
- Remove `type` from `$fillable`.
- Add `kelompokBelanja()` relationship.

### Seeders
#### [NEW] [KelompokBelanjaSeeder.php](file:///c:/Users/PC12/Project/rbakardinah/database/seeders/KelompokBelanjaSeeder.php)
- Seed initial groups: "Belanja Pegawai", "Belanja Barang & Jasa", "Belanja Modal".

#### [MODIFY] [AccountCodeSeeder.php](file:///c:/Users/PC12/Project/rbakardinah/database/seeders/AccountCodeSeeder.php)
- Update seeding logic to assign `kelompok_belanja_id`.

### Controllers
#### [MODIFY] [AccountCodeController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/AccountCodeController.php)
- Update `index`, `create`, `store`, `edit`, `update` to handle `kelompok_belanja_id`.
- Remove all references to the fake `type` field.

### Views
#### [MODIFY] [admin/account-codes/index.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/admin/account-codes/index.blade.php)
- Replace "Type" column with "Kelompok Belanja".

#### [MODIFY] [admin/account-codes/create.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/admin/account-codes/create.blade.php)
- Replace "Type" dropdown with "Kelompok Belanja" select.

#### [MODIFY] [admin/account-codes/edit.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/admin/account-codes/edit.blade.php)
- Replace "Type" dropdown with "Kelompok Belanja" select.

## Verification Plan

### Automated Tests
- Run `php artisan test` to check if any existing logic (like Pagu setting) is broken.
- Update `PaguTest` to reflect the new `AccountCode` creation requirement.

### Manual Verification
1. Login as **Administrator**.
2. Navigate to **Account Code Management**.
3. Verify the "Kelompok Belanja" column is displayed correctly.
4. Try creating a new Account Code and assigning it to a group.
5. Verify the group name appears correctly in the list.
