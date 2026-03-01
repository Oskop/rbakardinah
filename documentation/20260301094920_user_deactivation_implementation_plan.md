# User Deactivation Instead of Deletion Implementation Plan

Modify the user management feature so that users cannot be deleted. Instead, they can be deactivated.

## Proposed Changes

### Database & Model
#### [NEW] [2026_03_01_094508_add_is_active_to_users_table.php](file:///c:/Users/PC12/Project/rbakardinah/database/migrations/2026_03_01_094508_add_is_active_to_users_table.php)
Add `is_active` boolean column to `users` table, default to `true`.

#### [MODIFY] [User.php](file:///c:/Users/PC12/Project/rbakardinah/app/Models/User.php)
Add `is_active` to `$fillable` array and cast it to `boolean`.

### Middleware
#### [NEW] [CheckUserActive.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Middleware/CheckUserActive.php)
Create middleware to check if the authenticated user is active. If not, log them out and redirect with an error message.

#### [MODIFY] [app.php](file:///c:/Users/PC12/Project/rbakardinah/bootstrap/app.php)
Register the `CheckUserActive` middleware globally or in the web group.

### Admin Component
#### [MODIFY] [UserController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Admin/UserController.php)
- **Remove `destroy` logic** (or change it to toggle status). I will repurpose it to toggle the `is_active` status.
- Update `store` and `update` to handle the `is_active` field.
- Update `index` to show status.

#### [MODIFY] [index.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/admin/users/index.blade.php)
- Replace "Delete" button with "Deactivate" or "Activate" button.
- Add status badge (Active/Inactive).

#### [MODIFY] [create.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/admin/users/create.blade.php)
Add "Active" checkbox (optional, default to checked).

#### [MODIFY] [edit.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/admin/users/edit.blade.php)
Add "Active" status toggle.

---

## Verification Plan

### Automated Tests
- Run `php artisan test` to ensure existing functionality isn't broken.

### Manual Verification
1. **Deactivate User**:
   - Log in as Admin.
   - Deactivate a user.
   - Verify status changes to "Inactive".
   - Try to log in as that user and verify access is denied.
2. **Reactivate User**:
   - Log in as Admin.
   - Reactivate the user.
   - Verify they can log in again.
3. **No Delete**:
   - Verify there is no delete button/route available for users.
4. **Self-Deactivation**:
   - Ensure Admin cannot deactivate themselves (similar to previous delete protection).
