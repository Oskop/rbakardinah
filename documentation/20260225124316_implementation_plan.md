# Implementation Plan - Kelompok Belanja Master Management

Implement a complete CRUD interface for Administrators to manage `Kelompok Belanja` (Expense Groups) master data.

## Proposed Changes

### Controllers
#### [NEW] [KelompokBelanjaController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/KelompokBelanjaController.php)
- Implement `index`, `create`, `store`, `edit`, `update`, and `destroy` methods.

### Routes
#### [MODIFY] [web.php](file:///c:/Users/PC12/Project/rbakardinah/routes/web.php)
- Add `kelompok-belanja` resource route within the `admin` middleware group.

### Views
#### [NEW] [admin/kelompok-belanjas/index.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/admin/kelompok-belanjas/index.blade.php)
- Display table of Kelompok Belanja with Actions (Edit/Delete).

#### [NEW] [admin/kelompok-belanjas/create.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/admin/kelompok-belanjas/create.blade.php)
- Form to add new Kelompok Belanja.

#### [NEW] [admin/kelompok-belanjas/edit.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/admin/kelompok-belanjas/edit.blade.php)
- Form to edit existing Kelompok Belanja.

#### [MODIFY] [layouts/navigation.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/layouts/navigation.blade.php)
- Add a new menu item "Kelompok Belanja" for Administrators.

## Verification Plan

### Manual Verification
1. Login as **Administrator**.
2. Verify the new **Kelompok Belanja** menu item exists.
3. Navigate to the page and see the list of existing groups (seeded earlier).
4. Create a new group called "Test Group".
5. Edit the new group to "Updated Group".
6. Delete the "Updated Group".
7. Verify functionality is correct and UI matches existing master data pages.
