# Implementation Plan - Phase 5: Budget Logic & Locking

Implement the Admin interface for setting Global Pagu and enforce budget locking across the system.

## Proposed Changes

### Routes
#### [MODIFY] [web.php](file:///c:/Users/PC12/Project/rbakardinah/routes/web.php)
- Add routes for Admin to manage Pagu Global (index, store/update).

### Controllers
#### [NEW] [RbaAccountPaguController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Admin/RbaAccountPaguController.php)
- `index()`: Display a table of all account codes. For each, show:
  - Total Requested (Sum of nominal_request where it's the latest version).
  - Input field for `nominal_pagu`.
- `store()`: Save or update the `nominal_pagu` for a specific account code in a header.

### Models & Logic
#### [MODIFY] [RbaDetail.php](file:///c:/Users/PC12/Project/rbakardinah/app/Models/RbaDetail.php)
- Add a scope or method to get only the latest version details? (Actually `RbaDetail` itself is the transactional line, it has many attachments. The `nominal_request` is on the detail line).
- *Correction:* The SRS says "Operator mengunggah PDF baru (Versi 2) pada item `rba_details` yang sama". So `nominal_request` on the `RbaDetail` is what needs to be adjusted.

#### [MODIFY] [RbaDetailPolicy.php](file:///c:/Users/PC12/Project/rbakardinah/app/Policies/RbaDetailPolicy.php)
- Ensure the policy is registered and used in controllers.
- Add checks for Admin setting Pagu.

### Views
#### [NEW] [pagu_index.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/admin/headers/pagu.blade.php)
- Table for Admin to input Pagu for each Account Code associated with an RBA Header.

#### [MODIFY] [show.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/operator/submissions/show.blade.php)
- Add visual indicator for "Pagu Status":
  - Show Global Pagu for the account.
  - Show if total requests for that account exceed Pagu.

## Verification Plan

### Automated Tests
- Create `tests/Feature/Admin/PaguTest.php`:
  - Admin can set Pagu.
  - Setting Pagu locks the details for that account.
- Update `tests/Feature/Operator/RbaDetailTest.php`:
  - Test that operator sees warning if total > Pagu.
  - Test that operator cannot edit nominal if Pagu is set.

### Manual Verification
1. Login as Admin.
2. Go to RBA Header -> Set Pagu Global.
3. Input Pagu for "Belanja Gaji".
4. Login as Operator.
5. Try to edit an item with "Belanja Gaji" -> Should be locked for nominal.
6. Verify total vs Pagu indicator in Operator Workboard.
