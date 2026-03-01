# Implementation Plan - Refactoring RBA Periods

Refactor the `RbaPeriod` model to be a simple master list (Lookup Table) containing only `id` and `name`. Remove `year` and `status` fields which are better handled at the `RbaHeader` level.

## Proposed Changes

### Database
#### [NEW] [Migration](file:///c:/Users/PC12/Project/rbakardinah/database/migrations/2026_02_25_000000_simplify_rba_periods_table.php)
- Create migration to drop `year` and `status` columns from `rba_periods`.

### Models
#### [MODIFY] [RbaPeriod.php](file:///c:/Users/PC12/Project/rbakardinah/app/Models/RbaPeriod.php)
- Update `$fillable` to only include `name`.

### Seeders
#### [MODIFY] [RbaPeriodSeeder.php](file:///c:/Users/PC12/Project/rbakardinah/database/seeders/RbaPeriodSeeder.php)
- Remove `year` from the seeder. Seed only the 6 core phase names.

### Controllers
#### [MODIFY] [RbaPeriodController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/RbaPeriodController.php)
- Update `store` and `update` methods to only validate and save `name`.

#### [MODIFY] [RbaHeaderController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/RbaHeaderController.php)
- Update `create()` to fetch all periods without filtering by `status`.

### Views
#### [MODIFY] [admin/periods/index.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/admin/periods/index.blade.php)
- Update table to show only `ID` and `Name`.

#### [MODIFY] [admin/periods/create.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/admin/periods/create.blade.php)
- Update form to only input `Name`.

#### [MODIFY] [admin/periods/edit.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/admin/periods/edit.blade.php)
- Update form to only edit `Name`.

## Verification Plan

### Automated Tests
- Run `php artisan test` to identify breaking changes in existing tests (especially `PaguTest` and `RbaDetailTest`).
- Fix tests by updating their factory/creation logic for `RbaPeriod`.

### Manual Verification
1. Login as **Administrator**.
2. Navigate to **Periods**.
3. Verify only Period Names are displayed.
4. Add a new Period Name.
5. Edit an existing Period Name.
6. Navigate to **RBA Headers** -> **Add New Header**.
7. Verify the dropdown for Period correctly shows the names from the master list.
