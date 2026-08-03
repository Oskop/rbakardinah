# Implementation Plan - Rename Project to SIPAKAR

Rename the project name from **DIPAESI** to **SIPAKAR** (*Sistem Perencanaan dan Penganggaran RSUD Kardinah*), updating environment variables, view titles, branding elements, and footer text across the application.

## Proposed Changes

### Configuration & Environment
#### [MODIFY] [.env](file:///c:/Users/PC12/Project/rbakardinah/.env)
- Update `APP_NAME` from `"DIPAESI"` to `"SIPAKAR"`.

---

### Layouts & Views
#### [MODIFY] [guest.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/layouts/guest.blade.php)
- Update default `<title>` fallback from `'DIPAESI'` to `'SIPAKAR'`.
- Update logo branding text from `DIPAESI` to `SIPAKAR`.
- Update footer text to reference `SIPAKAR. Sistem Perencanaan dan Penganggaran RSUD Kardinah.`.

#### [MODIFY] [app.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/layouts/app.blade.php)
- Update default `<title>` fallback from `'Laravel'` to `'SIPAKAR'`.

#### [MODIFY] [welcome.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/welcome.blade.php)
- Update `<title>` to `SIPAKAR - Sistem Perencanaan dan Penganggaran RSUD Kardinah`.
- Update header/logo text to `SIPAKAR`.
- Update hero badge text from `DIGITALISASI PERENCANAAN DAN EVALUASI.` to `SISTEM PERENCANAAN DAN PENGANGGARAN RSUD KARDINAH.`.
- Update footer copyright text to reference `SIPAKAR`.

---

### Documentation
#### [NEW] [20260731133903_implementation_plan_Rename_Project_To_SIPAKAR.md](file:///c:/Users/PC12/Project/rbakardinah/documentation/20260731133903_implementation_plan_Rename_Project_To_SIPAKAR.md)
- Save a copy of this implementation plan in the `documentation/` folder using the repository's timestamp naming convention.

## Verification Plan

### Manual Verification
- Access the application landing page (`welcome.blade.php`) and guest layout (`login` / `guest.blade.php`) in the browser.
- Verify browser tab titles display **SIPAKAR - Sistem Perencanaan dan Penganggaran RSUD Kardinah** or **SIPAKAR**.
- Verify header logos, badge captions, and footer copyrights show **SIPAKAR**.
