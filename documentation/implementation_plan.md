# Frontend Setup (Bun) & Master Data Management Plan

This plan outlines the steps to transition the frontend development environment to use Bun and implement the management interface for master data (Units, Account Codes, and Periods).

## User Review Required

> [!IMPORTANT]
> We will be using **Bun** instead of Node.js for dependency management and running the dev server as requested.

## Proposed Changes

### Frontend Environment
---

#### [MODIFY] [package.json](file:///c:/Users/PC12/Project/rbakardinah/package.json)
Ensure scripts are compatible with bun.

#### [NEW] [bun.lock](file:///c:/Users/PC12/Project/rbakardinah/bun.lock)
Generated after running `bun install`.

### Master Data Management
---

#### [NEW] [UnitController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/UnitController.php)
#### [NEW] [AccountCodeController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/AccountCodeController.php)
#### [NEW] [RbaPeriodController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/RbaPeriodController.php)

#### [MODIFY] [web.php](file:///c:/Users/PC12/Project/rbakardinah/routes/web.php)
Add resource routes for master data.

#### [NEW] [index.blade.php (Units)](file:///c:/Users/PC12/Project/rbakardinah/resources/views/units/index.blade.php)
#### [NEW] [index.blade.php (Account Codes)](file:///c:/Users/PC12/Project/rbakardinah/resources/views/account-codes/index.blade.php)
#### [NEW] [index.blade.php (Periods)](file:///c:/Users/PC12/Project/rbakardinah/resources/views/periods/index.blade.php)

## Verification Plan

### Automated Tests
- Run `bun install` to verify dependency resolution.
- Run `bun run dev` to ensure Vite starts correctly.
- (Optional) Build PHPUnit tests for CRUD logic.

### Manual Verification
- Access `/admin/units`, `/admin/account-codes`, and `/admin/periods`.
- Perform CRUD operations for each master data type.
