# Individual RBA Detail Validation Implementation Plan

Modify the RBA validation process to allow Supervisors to validate or unvalidate each detail line item individually.

## Proposed Changes

### Database Changes
- [NEW] Create migration to add `is_validated`, `validated_at`, and `validated_by` to `rba_details` table.

### Model Updates
- [MODIFY] [RbaDetail.php](file:///c:/Users/PC12/Project/rbakardinah/app/Models/RbaDetail.php)
    - Add new fields to `$fillable`.
    - Add `is_validated` to `casts` as boolean.
    - Add `validator` relationship to `User`.

### Controller Updates
- [MODIFY] [ReviewController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Supervisor/ReviewController.php)
    - Add `toggleDetailValidation(RbaDetail $detail)` method.
    - Ensure only Supervisors from the same unit can toggle validation.
    - Update timestamps and user ID upon validation.

### Route Updates
- [MODIFY] [web.php](file:///c:/Users/PC12/Project/rbakardinah/routes/web.php)
    - Add `POST` route for detail validation toggle: `supervisor/details/{detail}/toggle-validation`.

### View Updates
- [MODIFY] [show.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/supervisor/submissions/show.blade.php)
    - Add a "Validasi" column in the details table.
    - Provide "Validate" (green) and "Unvalidate" (red/gray) buttons for each row.
    - Keep the global "Finalize" button but maybe add a check or visual cue if not all items are validated.

---

## Verification Plan

### Manual Verification
1. **Login as Supervisor**:
   - Navigate to an RBA submission review page.
   - Click "Validate" on a specific row.
   - Verify the row status updates visually.
   - Click "Unvalidate" on the same row.
   - Verify the status reverts.
2. **Access Control**:
   - Attempt to toggle validation on a detail belonging to another unit (via manual request if possible) and verify 403 Forbidden.
3. **Data Integrity**:
   - Check database records to ensure `is_validated`, `validated_at`, and `validated_by` are correctly populated.
