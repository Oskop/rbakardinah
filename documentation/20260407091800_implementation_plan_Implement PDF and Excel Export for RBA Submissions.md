# Implement PDF and Excel Export for RBA Submissions

This plan details the steps to add "Cetak PDF" and "Cetak Excel" functionality to the RBA Submissions hierarchy view. The exported files will accurately reflect the nested hierarchical structure of account codes, usulan, and pagu values.

## User Review Required

- **Package Installation**: We will install two new packages:
  - `barryvdh/laravel-dompdf` for PDF generation.
  - `maatwebsite/excel` for Excel generation (installed with `--ignore-platform-req=php+` to support the bleeding edge PHP 8.5.3 environment).
- Please confirm if you have any preferred styling for the exports (e.g. specific paper size, orientation, or colors). By default, the PDF will be A4 landscape.

## Proposed Changes

### Configuration & Packages
- Install `barryvdh/laravel-dompdf` and `maatwebsite/excel`.
- Register the packages if necessary (Laravel auto-discovery handles this mostly).

***

### App\Http\Controllers\RbaHeaderController
#### [MODIFY] [RbaHeaderController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/RbaHeaderController.php)
- Add a new private method `getReportData(\App\Models\RbaHeader $header)` to house the hierarchical data generation logic currently inside the `show` method.
- Update the `show` method to use this new private method, preventing code duplication.
- Add an `exportPdf(\App\Models\RbaHeader $header)` method to load the export view and stream a PDF download using DOMPDF.
- Add an `exportExcel(\App\Models\RbaHeader $header)` method to download the Excel file using Maatwebsite Excel.

***

### App\Exports
#### [NEW] [RbaExport.php](file:///c:/Users/PC12/Project/rbakardinah/app/Exports/RbaExport.php)
- Create a new Export class implementing `FromView` and `ShouldAutoSize` to render the Excel file directly from a Blade view template.

***

### Routes
#### [MODIFY] [web.php](file:///c:/Users/PC12/Project/rbakardinah/routes/web.php)
- Add GET routes `headers/{header}/export-pdf` pointing to `RbaHeaderController@exportPdf`.
- Add GET routes `headers/{header}/export-excel` pointing to `RbaHeaderController@exportExcel`.

***

### Views
#### [MODIFY] [show.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/admin/headers/show.blade.php)
- Add "Cetak PDF" and "Cetak Excel" buttons next to the "Set Pagu Global" and "Back to List" buttons at the top of the view.

#### [NEW] [export.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/admin/headers/export.blade.php)
- Create a clean, unstyled (basic HTML) version of the hierarchical table. This view will be used by both the PDF and Excel generators to ensure the exported documents are formatted correctly without web-specific CSS/JS interference.

## Verification Plan

### Automated/Manual Verification
- I will run `php artisan serve` and manually verify that:
  - Clicking "Cetak PDF" downloads a readable PDF with all hierarchical rows and correct indentation.
  - Clicking "Cetak Excel" downloads an `.xlsx` file where columns are auto-sized and hierarchical data is appropriately nested and formatted.
  - The main "View Submissions" web view still functions correctly after refactoring the controller logic.
