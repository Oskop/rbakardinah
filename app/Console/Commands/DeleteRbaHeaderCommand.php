<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RbaHeader;
use App\Services\HardDeleteArchiveService;

class DeleteRbaHeaderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rba:delete-header {id : The ID of the RBA Header} {--reason= : Reason for deletion} {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely hard delete an RBA Header and snapshot its data into hard_delete_archives for 100% restoration capability.';

    /**
     * Execute the console command.
     */
    public function handle(HardDeleteArchiveService $archiveService)
    {
        $id = $this->argument('id');
        $header = RbaHeader::with('period')->find($id);

        if (!$header) {
            $this->error("RBA Header with ID #{$id} not found.");
            return 1;
        }

        $periodName = $header->period->name ?? 'None';
        $this->warn("Target RBA Header: ID #{$header->id} | Year: {$header->year} | Period: {$periodName}");

        if (!\App\Models\SystemSetting::isRbaDeletionAllowed() && !$this->option('force')) {
            $this->warn("Pemberitahuan: Fitur penghapusan RBA saat ini sedang terkunci di Pengaturan Sistem.");
            if (!$this->confirm('Apakah Anda ingin mem-bypass kunci sistem dan tetap melanjutkan penghapusan?')) {
                $this->info('Operasi dibatalkan.');
                return 0;
            }
        }

        $validatedCount = \App\Models\RbaDetail::whereIn('rba_submission_id', $header->submissions->pluck('id'))
            ->where('is_validated', true)
            ->count();

        if ($validatedCount > 0 && !$this->option('force')) {
            $this->error("This RBA Header has {$validatedCount} validated details. Use --force to proceed.");
            return 1;
        }

        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to hard delete this RBA Header and move it to the archive?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $reason = $this->option('reason') ?: 'Manual deletion via CLI command';
        $archive = $archiveService->deleteRbaHeader($header, $reason, 1);

        $this->info("Successfully deleted RBA Header #{$id}.");
        $this->info("Snapshot saved to hard_delete_archives (Archive ID: #{$archive->id}).");
        $this->info("You can restore it anytime using: php artisan rba:restore-header {$archive->id}");

        return 0;
    }
}

