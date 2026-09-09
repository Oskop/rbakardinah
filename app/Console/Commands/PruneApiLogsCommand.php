<?php

namespace App\Console\Commands;

use App\Models\ApiAccessLog;
use Illuminate\Console\Command;

class PruneApiLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:logs-prune 
                            {--days=30 : Jumlah hari log yang dipertahankan (default: 30 hari)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bersihkan rekaman log akses REST API yang telah usang';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        if ($days < 1) {
            $days = 30;
        }

        $cutoffDate = now()->subDays($days);
        $deleted = ApiAccessLog::where('created_at', '<', $cutoffDate)->delete();

        $this->info("Berhasil membersihkan {$deleted} rekaman log akses API yang dibuat sebelum {$cutoffDate->format('Y-m-d H:i:s')} (>{$days} hari).");

        return self::SUCCESS;
    }
}
