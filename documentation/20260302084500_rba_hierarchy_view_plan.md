# Hierarchical RBA Submission View for Admin

This plan outlines the implementation of a detailed, hierarchical report for RBA submissions in the admin panel, as requested to match the provided layout.

## User Review Required

> [!IMPORTANT]
> The hierarchy is built based on the strings in the `code` column of the `AccountCode` table (e.g., `5.1.01`). If parent codes (like `5` or `5.1`) are missing from the database, they will not be displayed, or will be displayed as partial rows without names. We recommend ensuring all hierarchy levels exist in the `AccountCode` master data.

## Proposed Changes

### Controllers

#### [MODIFY] [RbaHeaderController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/RbaHeaderController.php)
- Update `show` method to fetch more than just unit statuses.
- Fetch all `AccountCode` records.
- Fetch all `RbaDetail` records for all submissions within this header.
- Fetch all `RbaAccountPagu` records for this header.
- Implement logic to:
    - Group `RbaDetail` by `account_code_id`.
    - Map `RbaAccountPagu` by `account_code_id`.
    - Calculate recursive sums for "Usulan" and "Pagu" for each parent account level.
    - Organize the data into a flat list with hierarchy metadata (level, is_parent, etc.) for easy rendering.

### Views

#### [MODIFY] [show.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/admin/headers/show.blade.php)
- Replace the current unit status table with the detailed hierarchical report.
- The table will include columns:
    1. **KODE REKENING**: Following the dotted hierarchy.
    2. **URAIAN BELANJA**: With indentation based on depth.
    3. **USULAN (Rp)**: Formatted currency.
    4. **PAGU (Rp)**: Formatted currency from Global Pagu.
    5. **SUPERVISOR**: Name of validator (for leaf nodes).
    6. **OPERATOR**: Name of creator (for leaf nodes).
- Styling:
    - Bold rows for parent categories.
    - Subtle indentation for child codes.
    - Grid-like borders as shown in the image.

## Verification Plan

### Automated Tests
- Create a test `tests/Feature/Admin/RbaHierarchyViewTest.php`:
    - Verify that the hierarchical report loads for a given header.
    - Verify that usulan values are summed correctly for parent codes.

### Manual Verification
1. Login as Admin.
2. Go to RBA Headers.
3. Click "View Submissions" on an active RBA.
4. Verify the tree structure matches the DOT-separated codes.
5. Verify sums are correct.
6. Verify "Supervisor" and "Operator" columns show who handled the details.
7. Compare the final look with the provided screenshot.
