# Individual RBA Detail Rejection Walkthrough

I have implemented a feature that allows Supervisors to reject specific RBA detail items instead of just validating them. Supervisors must provide a reason for each rejection.

## Changes Made

### 1. Database & Model
- Added rejection fields to the `rba_details` table via migration [2026_03_01_053537_add_rejection_fields_to_rba_details_table.php](file:///c:/Users/PC12/Project/rbakardinah/database/migrations/2026_03_01_053537_add_rejection_fields_to_rba_details_table.php):
    - `is_rejected`: Boolean flag.
    - `rejected_at`: Timestamp of rejection.
    - `rejected_by`: Supervisor who rejected the item.
    - `rejection_reason`: Reason for rejection.
- Updated [RbaDetail.php](file:///c:/Users/PC12/Project/rbakardinah/app/Models/RbaDetail.php) model with the new fields and the `rejector` relationship.

### 2. Logic & Routes
- Implemented `rejectDetail` in [ReviewController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Supervisor/ReviewController.php).
- Modified `toggleDetailValidation` to clear the rejection status if an item is subsequently validated.
- Added a new POST route `supervisor/details/{detail}/reject` in [web.php](file:///c:/Users/PC12/Project/rbakardinah/routes/web.php).

### 3. User Interface
- Updated [show.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/supervisor/submissions/show.blade.php):
    - Added a **"✖ Tolak"** button next to the validation button.
    - Added a JavaScript helper that prompts the Supervisor for a reason before submitting the rejection.
    - Displays the **"Ditolak"** status in red, along with the supervisor's name and the specific rejection reason.

## Verification Highlights
- **Reason Required**: Verified that the UI prevents submission without a reason.
- **Mutually Exclusive**: Confirmed that an item cannot be both "Validated" and "Rejected" at the same time.
- **Visual Feedback**: The interface clearly distinguishes between pending, validated, and rejected items with color-coded status badges and meta-information.
