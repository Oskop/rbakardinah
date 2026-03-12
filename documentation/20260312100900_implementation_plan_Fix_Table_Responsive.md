# Implementation Plan - Fix Table Responsiveness

The goal is to ensure all master data tables are responsive on small screens by wrapping them in a div with `overflow-x-auto`. We will also update the `Kelompok Belanja` index page to display the newly added `kode` column.

## Proposed Changes

### Views

#### [MODIFY] [admin/account-codes/index.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/admin/account-codes/index.blade.php)
- Wrap the table in `<div class="overflow-x-auto">`.

#### [MODIFY] [admin/units/index.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/admin/units/index.blade.php)
- Wrap the table in `<div class="overflow-x-auto">`.

#### [MODIFY] [admin/periods/index.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/admin/periods/index.blade.php)
- Wrap the table in `<div class="overflow-x-auto">`.

#### [MODIFY] [admin/kelompok-belanjas/index.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/admin/kelompok-belanjas/index.blade.php)
- Wrap the table in `<div class="overflow-x-auto">`.
- Add a "Code" column to the table header and body.

## Verification Plan

### Automated Tests
- No automated tests for CSS/responsiveness.

### Manual Verification
- Manually inspect the pages on a small screen (or resize the browser) to ensure horizontal scroll bars appear and the layout doesn't break.
- Verify that the `Kelompok Belanja` index page now shows the [Code](file:///c:/Users/PC12/Project/rbakardinah/app/Models/AccountCode.php#7-16) column.
