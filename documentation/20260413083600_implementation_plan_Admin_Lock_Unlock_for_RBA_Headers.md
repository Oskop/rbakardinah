# Implementation Plan - Admin Lock/Unlock for RBA Headers

This plan aims to provide Administrators with the ability to lock and unlock the global status of an RBA Header, which controls whether Operators can add new expenditure details.

## Proposed Changes

### Backend

#### [MODIFY] [web.php](file:///c:/Users/PC12/Project/rbakardinah/routes/web.php)
- Add a new POST route `admin.headers.toggle-status` pointing to a new method in `RbaHeaderController`.

#### [MODIFY] [RbaHeaderController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/RbaHeaderController.php)
- Add a `toggleStatus` method that switches `status_global` between `'Draft'` and `'Locked'`.

### Frontend

#### [MODIFY] [index.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/admin/headers/index.blade.php)
- Add a "Lock/Unlock" button in the actions column for each RBA Header.
- Use visual indicators (colors/icons) to show the current lock state.

## Verification Plan

### Manual Verification
- Log in as Admin.
- Navigate to RBA Header list.
- Click "Lock" on a Draft header.
- Verify status changes to "Locked".
- Log in as Operator.
- Verify "+ Tambah Rincian" button is hidden.
- Log in as Admin again.
- Click "Unlock" on the header.
- Log in as Operator and verify the button reappears.
