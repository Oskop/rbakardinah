# RBA Detail Rejection and Resubmission Improvements

This plan addresses the issue where rejected RBA details do not show rejection reasons and their actions remain locked. It also introduces item-level submission as requested.

## Proposed Changes

### Database & Models

#### [MODIFY] [RbaDetail.php](file:///c:/Users/PC12/Project/rbakardinah/app/Models/RbaDetail.php)
- Add `Illuminate\Database\Eloquent\SoftDeletes` trait.
- Add `is_submitted` to `$fillable`.
- Update `casts` to include `is_submitted` as boolean.

#### [NEW] [Migration: add_soft_deletes_and_is_submitted_to_rba_details](file:///c:/Users/PC12/Project/rbakardinah/database/migrations/2026_03_02_000000_add_soft_deletes_and_is_submitted_to_rba_details_table.php)
- Add `deleted_at` column (soft deletes).
- Add `is_submitted` boolean column, default `false`.

---

### Operator Views

#### [MODIFY] [show.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/operator/submissions/show.blade.php)
- Update the table to include a "Status" column (Draft, Submitted, Validated, Rejected).
- Display `rejection_reason` under the description or in a new column when `is_rejected` is true.
- Unlock "Edit" and "Revisi PDF" actions if `is_rejected` is true or if it's still a `Draft`.
- Add a "Submit to Supervisor" button for each item if it's in `Draft` or `Rejected` state.
- Add a "Delete" (soft delete) button for each item.

---

### Controllers

#### [MODIFY] [DetailController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Operator/DetailController.php)
- Update `edit` and `update` to allow modifications if the item is `Rejected`.
- Implement `destroy` to perform a soft delete.
- Add `submitItem` method to handle per-item submission (set `is_submitted = true`, `is_rejected = false`, `rejection_reason = null`).
- When an item is submitted, also ensure the parent `RbaSubmission` status is updated to `Pending Supervisor` if it was `Draft`.

---

### Supervisor Views (Optional but recommended)

#### [MODIFY] [show.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/supervisor/submissions/show.blade.php)
- Ensure the supervisor only sees items where `is_submitted` is true (or clear indicator for submitted vs unsubmitted items).

## Verification Plan

### Automated Tests
- Run existing tests: `php artisan test tests/Feature/Operator/RbaDetailTest.php`
- Create a new test `tests/Feature/Operator/RbaDetailResubmissionTest.php` to verify:
    - Item can be soft deleted.
    - Rejected item can be edited.
    - Rejected item can be resubmitted.
    - Rejection reason is visible (check HTML content).

### Manual Verification
1. Login as Operator.
2. Create an RBA detail.
3. Submit the item to Supervisor.
4. Login as Supervisor.
5. Reject the item with a reason.
6. Login as Operator.
7. Verify the rejection reason is visible.
8. Verify "Edit" and "Delete" actions are unlocked.
9. Edit the item and resubmit.
10. Delete the item and verify it disappears from the list (but exists in DB with `deleted_at`).
