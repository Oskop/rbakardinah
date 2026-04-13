# Walkthrough - Admin Lock/Unlock for RBA Headers

I have implemented the ability for Administrators to globally lock and unlock the RBA input process.

## Changes Made

### Backend
- **Route**: Added `POST /admin/headers/{header}/toggle-status` to `web.php`.
- **Controller**: Added `toggleStatus` method to `RbaHeaderController` to flip the `status_global` between `Draft` and `Locked`.

### Frontend
- **Admin Dashboard**: Added a "Lock RBA" / "Unlock RBA" button to the RBA Header list (`admin.headers.index`).
- **Operator View**: The "+ Tambah Rincian" button and individual item actions (Edit/Delete/Submit) are now automatically disabled if the Admin locks the RBA (status is no longer `Draft`).

## Verification Results

### Logic Check
- [x] Admin can toggle status.
- [x] Status `Draft` -> Operator can add/edit.
- [x] Status `Locked` -> Operator UI is locked.
- [x] Policy checks correctly block API/Form submissions when `Locked`.

> [!IMPORTANT]
> When the Admin clicks **Lock RBA**, the status changes to **Locked**. In this state, the "+ Tambah Rincian" button will disappear for all operators in all units for that specific RBA period.
