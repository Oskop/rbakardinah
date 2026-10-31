<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HardDeleteArchive;
use App\Services\HardDeleteArchiveService;
use Exception;

class RestoreRbaHeaderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rba:restore-header {archive_id : The ID of the archive in hard_delete_archives} {--force : Force restoration without confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '100% faithfully restore an archived RBA Header and all its relations back to operational tables.';

    /**
     * Execute the console command.
     */
    public function handle(HardDeleteArchiveService $archiveService)
    {
        $archiveId = $this->argument('archive_id');
        $archive = HardDeleteArchive::find($archiveId);

        if (!$archive) {
            $this->error("Archive record with ID #{$archiveId} not found.");
            return 1;
        }

        if ($archive->isRestored()) {
            $this->error("This archive was already restored on {$archive->restored_at}.");
            return 1;
        }

        $this->info("Target Archive: {$archive->label} (Deleted at: {$archive->deleted_at})");
        $this->info("Reason: {$archive->reason}");

        if (!$this->option('force')) {
            if (!$this->confirm('Proceed with 100% faithful restoration of this RBA Header?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        try {
            $restored = $archiveService->restoreRbaHeader($archive, 1);
            $this->info("Successfully restored RBA Header #{$restored->id} ({$restored->year} - " . ($restored->period->name ?? 'RBA') . ") back into operational tables!");
            return 0;
        } catch (Exception $e) {
            $this->error("Restore failed: " . $e->getMessage());
            return 1;
        }
    }
}
