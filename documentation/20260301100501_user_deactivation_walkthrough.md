# User Deactivation Walkthrough

I have modified the user management feature so that users can no longer be deleted. Instead, they can be deactivated and reactivated.

## Changes Made

### 1. Database
- Added `is_active` boolean column to the `users` table via migration [2026_03_01_024959_add_is_active_to_users_table.php](file:///c:/Users/PC12/Project/rbakardinah/database/migrations/2026_03_01_024959_add_is_active_to_users_table.php).
- Updated [User.php](file:///c:/Users/PC12/Project/rbakardinah/app/Models/User.php) model to include `is_active` in fillable and casts.

### 2. Middleware
- Created [CheckUserActive.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Middleware/CheckUserActive.php) middleware.
- Registered it in [bootstrap/app.php](file:///c:/Users/PC12/Project/rbakardinah/bootstrap/app.php) to automatically log out and block inactive users from accessing the system.

### 3. Logic & UI
- Updated [UserController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Admin/UserController.php):
    - Changed `destroy` method to toggle the `is_active` status instead of deleting the user.
    - Updated `update` to handle manual status changes from the edit form.
- Updated [index.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/admin/users/index.blade.php):
    - Added "Status" column with badges (Active/Inactive).
    - Replaced "Delete" button with "Deactivate" or "Activate" button.
- Updated [edit.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/admin/users/edit.blade.php):
    - Added "Status" dropdown to manually set a user's status.

## Verification Results

### Protection
- **Self-Deactivation**: Administrators are blocked from deactivating their own account.
- **Access Control**: When a user is deactivated, their current session is invalidated, and they are redirected to the login page with an error message. They cannot log back in until reactivated.
- **No Deletion**: The `destroy` route is now strictly used for toggling status, ensuring data integrity.
