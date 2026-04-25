Flexibility in Expenditure Details During Pagu Issuance
Currently, when an RBA year is marked as Locked (status pagu issued), operators are completely restricted from adding or modifying expenditure details. This task implements an exception: if a specific account code (nomor rekening) has no budget allocation (Pagu) or if the Pagu is 0, operators should still be able to add, edit, and submit details for that account. Supervisors should also be able to verify and validate these items.

Proposed Changes
Authorization Layer
[MODIFY] 

RbaDetailPolicy.php
Implement a helper method to check if an account code is "locked" based on its Pagu allocation rather than just the global header status.
Update create, update, and delete methods to allow actions if the account's Pagu is 0 or missing, even if the global status is Locked.
Ensure that once a Pagu > 0 is set, the account becomes locked for operators.
Backend Logic
[MODIFY] 

DetailController.php
Remove hardcoded status_global !== 'Draft' checks in edit, update, destroy, etc., and rely on Gate::authorize.
Add Gate authorization to submitItem and uploadVersion to ensure consistent permission enforcement.
Frontend (Operator)
[MODIFY] 

show.blade.php
Update the "+ Tambah Rincian" button visibility: it should now be visible whenever the global status is Draft OR if there are any accounts that can still be edited (though for simplicity, showing it and letting the form handle specific account selection is often better).
Update the action buttons (Edit, Ajukan, Hapus) to be visible for items whose account has no/zero pagu, regardless of the global Draft status.
Verification Plan
Automated Tests
I'll use the browser tool to:
Set a header to Locked status.
Try to add a detail for an account that HAS Pagu > 0 (should be denied).
Try to add a detail for an account that HAS NO Pagu or Pagu = 0 (should be allowed).
Edit an existing detail for an account with 0 Pagu (should be allowed).
Submit and verify as Supervisor (should be allowed).
Manual Verification
Check that the UI correctly reflects these permissions (buttons appearing/disappearing based on Pagu values).
Confirm that error messages are clear when an action is blocked due to an existing Pagu allocation.
NOTE

The term "Issue Pagu" in the user request translates to the Locked status in the code, which is triggered when the admin locks the RBA header. The core check will transition from checking the global status to checking the specific account's budget allocation.