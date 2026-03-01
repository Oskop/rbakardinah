# User Management Implementation Plan

Add a user management feature to the admin dashboard to allow administrators to manage users (CRUD operations).

## Proposed Changes

### Admin Component

#### [NEW] [UserController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Admin/UserController.php)
Implement CRUD logic for users:
- `index`: List all users with their units.
- `create`: Show form to create a new user.
- `store`: Validate and save new user (name, email, password, role, unit_id).
- `edit`: Show form to edit an existing user.
- `update`: Validate and update user (with optional password update).
- `destroy`: Delete a user.

#### [MODIFY] [web.php](file:///c:/Users/PC12/Project/rbakardinah/routes/web.php)
Add resource route for users within the admin group:
```php
Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
```

#### [NEW] [index.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/admin/users/index.blade.php)
Table view to list users with columns: Name, Email, Role, Unit, and Actions (Edit/Delete).

#### [NEW] [create.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/admin/users/create.blade.php)
Form to create a user with fields:
- Name (text)
- Email (email)
- Password (password)
- Role (select: Administrator, Supervisor, Operator)
- Unit (select from existing units)

#### [NEW] [edit.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/admin/users/edit.blade.php)
Form to edit a user (same fields as create, but password is optional).

#### [MODIFY] [navigation.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/layouts/navigation.blade.php)
Add "Users" link to the primary and responsive navigation for `Administrator`.

#### [MODIFY] [dashboard.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/admin/dashboard.blade.php)
Add a "Users" card/link to the admin dashboard for easier access.

---

## Verification Plan

### Manual Verification
1. **Login as Admin**: Ensure you can access the new "Users" menu.
2. **List Users**: Verify that the user list displays correctly, including the associated unit.
3. **Create User**:
   - Fill the form with valid data.
   - Verify that the user is created and redirected to the index page with a success message.
   - Test validation (e.g., duplicate email, empty fields).
4. **Edit User**:
   - Change name/role/unit.
   - Verify changes are saved.
   - Test password update (leave blank to keep current, fill to change).
5. **Delete User**:
   - Delete a user and confirm the action.
   - Verify user is removed from the table.
6. **Role Check**:
   - Log in as `Supervisor` or `Operator`.
   - Verify that the "Users" link is NOT visible.
   - Manually navigate to `/admin/users` and verify you get a 403 Forbidden error.
