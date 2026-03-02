## Hierarchical RBA View for Admin

This feature provides a formalized budget report for the admin panel, mirroring the layout of a professional budget spreadsheet.

### Key Enhancements
- **Automatic Hierarchy**: Account codes are automatically grouped by their prefix (e.g., 5 -> 5.1 -> 5.1.01).
- **Aggregated Totals**: "Usulan" and "Pagu" values are recursively summed from child items up to parent categories.
- **Formal Formatting**: The report includes a standard BLUD RSUD Kardinah header, currency formatting, and grid borders.
- **Workflow Transparency**: Added Supervisor and Operator columns to identify who handled each specific budget item.

### Changes Made
- **RbaHeaderController@show**: Added logic to build a tree structure from [AccountCode](file:///c:/Users/PC12/Project/rbakardinah/app/Models/AccountCode.php#7-16), [RbaDetail](file:///c:/Users/PC12/Project/rbakardinah/app/Models/RbaDetail.php#10-81), and [RbaAccountPagu](file:///c:/Users/PC12/Project/rbakardinah/app/Models/RbaAccountPagu.php#8-22).
- **admin.headers.show**: Replaced the simple status table with the new multi-column hierarchical table.
- **Styling**: Applied Tailwind and custom styles for indentation and category highlighting.
