# Implementation Plan - Phase 6: Reporting & Audit

Build the Supervisor's review dashboard, implement PDF version history tracking, and perform final UI/UX polishing.

## Proposed Changes

### Routes
#### [MODIFY] [web.php](file:///c:/Users/PC12/Project/rbakardinah/routes/web.php)
- Add routes for Supervisor to review submissions (`supervisor/submissions`).
- Add routes to view history for a specific detail (accessible by all roles).

### Controllers
#### [NEW] [ReviewController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Supervisor/ReviewController.php)
- `index()`: List all submissions for the supervisor's unit.
- `show()`: Show all items in a submission for review.
- `validate()`: Action to mark a submission as `Validated`.

#### [NEW] [HistoryController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/General/HistoryController.php)
- `show(RbaDetail $detail)`: List all versions of PDFs for the given item.

### Views
#### [NEW] [supervisor/submissions/index.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/supervisor/submissions/index.blade.php)
- Dashboard for Supervisor to track unit submissions.

#### [NEW] [supervisor/submissions/show.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/supervisor/submissions/show.blade.php)
- Detailed review page with "Validate" button.

#### [NEW] [general/history.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/general/history.blade.php)
- Simple list or modal-like view to see all PDF versions.

### UI/UX Polishing
- Improve navigation menus.
- Add hover effects and better empty states.
- Ensure consistent color scheme (Indigo/Slate/Blue).

## Verification Plan

### Automated Tests
- Create `tests/Feature/Supervisor/ReviewTest.php`:
  - Supervisor can view unit submissions.
  - Supervisor can validate submission.
  - Supervisor cannot edit items (Read-Only review).
- Create `tests/Feature/General/HistoryTest.php`:
  - Verify all attachment versions are displayed.

### Manual Verification
1. Login as **Supervisor**.
2. Navigate to Submissions.
3. Review an Operator's submission.
4. Click "Download PDF" to verify item details.
5. Click "Validate".
6. Login as **Administrator** and verify the submission shows as "Validated".
7. Check "PDF History" on an item that has multiple versions.
