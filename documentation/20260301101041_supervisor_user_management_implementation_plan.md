# Supervisor User Management Implementation Plan

Extend user management to allow `Supervisor` roles to manage users (add, edit, deactivate) restricted to their own unit.

## Proposed Changes

### Supervisor Component

#### [NEW] [UserController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Supervisor/UserController.php)
Implement unit-restricted CRUD logic for users:
- `index`: List users belonging to the supervisor's unit.
- `create`: Show form to create a new user (role fixed to 'Operator', unit fixed to supervisor's unit).
- `store`: Validate and save new user for the supervisor's unit.
- `edit`: Show form to edit a user from the same unit.
- `update`: Validate and update user (ensuring they stay in the same unit).
- `destroy`: Toggle `is_active` for users in the supervisor's unit.

#### [MODIFY] [web.php](file:///c:/Users/PC12/Project/rbakardinah/routes/web.php)
Add resource route for users within the supervisor group:
```php
Route::resource('users', \App\Http\Controllers\Supervisor\UserController::class);
```

### Views

#### [NEW] [index.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/supervisor/users/index.blade.php)
List users for the supervisor's unit.

#### [NEW] [create.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/supervisor/users/create.blade.php)
Form to create a user (unit and role are pre-set/hidden).

#### [NEW] [edit.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/supervisor/users/edit.blade.php)
Form to edit a user (restricted to users in the same unit).

### UI Integration

#### [MODIFY] [navigation.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/layouts/navigation.blade.php)
Add "Users" link for `Supervisor` role.

#### [MODIFY] [dashboard.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/supervisor/dashboard.blade.php)
Add "Users" card to supervisor dashboard.

---

## Verification Plan

### Manual Verification
1. **Login as Supervisor**:
   - Access the "Users" menu.
   - Verify that ONLY users from the same unit are displayed.
2. **Create User (as Supervisor)**:
   - Verify that the unit is automatically set to the supervisor's unit.
   - Verify that the role is restricted (e.g., only 'Operator' can be created).
3. **Edit User (as Supervisor)**:
   - Edit a user from the same unit.
   - Try to access the edit page of a user from another unit via URL manipulation and verify access is denied (403).
4. **Deactivate User (as Supervisor)**:
   - Deactivate/Reactivate a user from the same unit.
5. **Cross-Unit Access**:
   - Ensure a Supervisor CANNOT see or manage users from other units.
