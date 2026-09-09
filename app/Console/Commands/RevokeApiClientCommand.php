<?php

namespace App\Console\Commands;

use App\Models\ApiClient;
use Illuminate\Console\Command;

class RevokeApiClientCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:client-revoke 
                            {identifier : ID atau Prefix dari API Client yang akan dicabut}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mencabut / menonaktifkan akses API Key dari klien';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $id = $this->argument('identifier');

        $client = is_numeric($id)
            ? ApiClient::find($id)
            : ApiClient::where('key_prefix', 'like', "{$id}%")->first();

        if (!$client) {
            $this->error("API Client dengan ID/Prefix '{$id}' tidak ditemukan.");
            return self::FAILURE;
        }

        $client->update(['is_active' => false]);

        $this->info("Akses API Client [{$client->id}] '{$client->name}' ({$client->key_prefix}...) berhasil DINONAKTIFKAN.");

        return self::SUCCESS;
    }
}
