<?php

namespace App\Console\Commands;

use Database\Seeders\Permendagri108MasterBarangSeeder;
use Illuminate\Console\Command;

class ImportPermendagri108MasterBarangCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rkbmd:import-permendagri';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Impor seluruh daftar barang Level 7 dari Permendagri No. 108 Tahun 2016 ke tabel master_barangs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai impor master barang Permendagri No. 108 Tahun 2016 (Level 7)...');

        $seeder = new Permendagri108MasterBarangSeeder();
        $seeder->setCommand($this);
        $seeder->run();

        $this->newLine();
        $this->info('✓ Proses impor dan sinkronisasi master barang berhasil diselesaikan.');

        return Command::SUCCESS;
    }
}
