# Implementation Plan - Restrict Operator RBA Detail Visibility

The goal is to ensure that users with the 'Operator' role can only see and manage `rba_detail` records that they have personally created, even if other operators in the same unit have created other records.

## User Review Required

> [!IMPORTANT]
> This change will hide other operators' inputs from the unit workboard view. Operators will no longer see the "full picture" of their unit's submissions in the list, though the budget status (Pagu) will still reflect the total unit/global usage to avoid overshooting the budget.

## Proposed Changes

### [Operator Controllers & Policies]

#### [MODIFY] [SubmissionController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Operator/SubmissionController.php)
- Update the `show` method to filter the `details` relationship of the `RbaSubmission` model using an eager loading constraint: `details => function($q) { $q->where('created_by', Auth::id()); }`.

#### [MODIFY] [DetailController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Operator/DetailController.php)
- Update `edit`, `update`, and `destroy` methods to ensure operators can only access their own records by adding a check for `created_by`.

#### [MODIFY] [RbaDetailPolicy.php](file:///c:/Users/PC12/Project/rbakardinah/app/Policies/RbaDetailPolicy.php)
- Enforce ownership in the `update` and `delete` (if exists) methods to ensure unauthorized operators cannot bypass UI restrictions.

## Open Questions

1. **Should operators still see the total unit/global nominal?**
   - The current implementation of `headerTotals` in `SubmissionController@show` calculates the global sum across all units. I plan to keep this as is so the "Status Pagu" (Over/Covered) remains accurate against the global cap, but only show the operator's specific items in the list below.

## Verification Plan

### Manual Verification
1. Create two operator accounts in the same Unit.
2. Login as Operator A and add an item.
3. Login as Operator B and add an item.
4. Verify that Operator A *cannot* see Operator B's item, and vice versa.
5. Verify that the "Total Usulan (Unit)" or budget status still correctly accounts for both items if it's meant to be a unit-wide/global cap.
6. Attempt to edit Operator B's item via URL as Operator A and ensure it is blocked.
