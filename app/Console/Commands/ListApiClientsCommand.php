<?php

namespace App\Console\Commands;

use App\Models\ApiClient;
use Illuminate\Console\Command;

class ListApiClientsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:client-list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menampilkan daftar seluruh API Client yang terdaftar';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $clients = ApiClient::orderBy('id')->get();

        if ($clients->isEmpty()) {
            $this->warn('Belum ada API Client yang terdaftar. Gunakan "php artisan api:client-create <nama>" untuk membuat baru.');
            return self::SUCCESS;
        }

        $headers = ['ID', 'Nama Klien', 'Key Prefix', 'Status', 'Kedaluwarsa', 'Terakhir Digunakan', 'IP Terakhir'];
        $rows = [];

        foreach ($clients as $client) {
            $status = $client->is_active ? 'AKTIF' : 'NON-AKTIF';
            if ($client->isExpired()) {
                $status = 'KEDALUWARSA';
            }

            $rows[] = [
                $client->id,
                $client->name,
                $client->key_prefix . '...',
                $status,
                $client->expires_at ? $client->expires_at->format('d-m-Y H:i') : 'Selamanya',
                $client->last_used_at ? $client->last_used_at->format('d-m-Y H:i') : '-',
                $client->last_used_ip ?: '-',
            ];
        }

        $this->table($headers, $rows);

        return self::SUCCESS;
    }
}
