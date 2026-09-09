<?php

namespace App\Console\Commands;

use App\Models\ApiClient;
use Illuminate\Console\Command;

class CreateApiClientCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:client-create 
                            {name : Nama aplikasi atau rekanan pemegang API Key} 
                            {--expires-in-days= : Masa berlaku token dalam jumlah hari (opsional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate API Key baru untuk aplikasi rekanan / pihak ketiga';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = trim($this->argument('name'));
        $days = $this->option('expires-in-days');
        $expiresInDays = $days !== null ? (int) $days : null;

        if (empty($name)) {
            $this->error('Nama aplikasi atau klien tidak boleh kosong.');
            return self::FAILURE;
        }

        $result = ApiClient::createWithToken($name, $expiresInDays);
        $client = $result['client'];
        $plainToken = $result['plain_token'];

        $this->newLine();
        $this->info('================================================================');
        $this->info('  BERHASIL MEMBUAT API KEY KLIEN BARU (SIPAKAR RSUD KARDINAH)  ');
        $this->info('================================================================');
        $this->line("ID Klien       : <comment>{$client->id}</comment>");
        $this->line("Nama Aplikasi  : <comment>{$client->name}</comment>");
        $this->line("Key Prefix     : <comment>{$client->key_prefix}</comment>");
        $this->line("Status         : <fg=green;options=bold>AKTIF</>");
        $this->line("Kedaluwarsa    : " . ($client->expires_at ? "<comment>{$client->expires_at->format('d-m-Y H:i')} WIB</comment>" : "<comment>Tidak terbatas (Selamanya)</comment>"));
        $this->newLine();

        $this->warn('PENTING: Salin token rahasia di bawah ini sekarang. Token tidak akan ditampilkan lagi!');
        $this->newLine();
        $this->line("API TOKEN      : <fg=yellow;options=bold>{$plainToken}</>");
        $this->newLine();

        $this->info('Cara penggunaan oleh aplikasi rekanan:');
        $this->line("Header: <comment>Authorization: Bearer {$plainToken}</comment>");
        $this->line("Atau  : <comment>X-API-KEY: {$plainToken}</comment>");
        $this->newLine();

        return self::SUCCESS;
    }
}
