<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ExportTransactionalData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:export-transactional-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export transactional data to a seeder file for easy transfer between environments';

    /**
     * Tables to export in order of dependency.
     */
    protected $tables = [
        'rba_headers',
        'rba_submissions',
        'rba_account_pagus',
        'rba_details',
        'rba_attachments',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting data export...');

        $seederContent = "<?php\n\nnamespace Database\Seeders;\n\nuse Illuminate\Database\Seeder;\nuse Illuminate\Support\Facades\DB;\n\nclass TransactionalDataSeeder extends Seeder\n{\n    public function run(): void\n    {\n";

        foreach ($this->tables as $table) {
            $this->info("Exporting table: {$table}");
            $data = DB::table($table)->get();

            if ($data->isEmpty()) {
                $seederContent .= "        // No data found for table: {$table}\n";
                continue;
            }

            $seederContent .= "        // Table: {$table}\n";
            
            // Chunking the insert to avoid memory issues or large file sizes if many rows
            $data->chunk(100)->each(function ($chunk) use (&$seederContent, $table) {
                $rows = $chunk->map(function ($row) {
                    return (array) $row;
                })->toArray();

                $export = var_export($rows, true);
                // Fix for PHP 8.1+ internal representation vs valid array syntax if needed, 
                // but var_export is generally safe for arrays.
                
                $seederContent .= "        DB::table('{$table}')->insert({$export});\n\n";
            });
        }

        $seederContent .= "    }\n}\n";

        $path = database_path('seeders/TransactionalDataSeeder.php');
        File::put($path, $seederContent);

        $this->info("Seeder file created at: {$path}");
        $this->info('Export completed successfully!');
    }
}
