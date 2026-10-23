<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class Permendagri108MasterBarangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = base_path('data/permendagri_108_2016/tabel_2_6_sub_sub_rincian_objek.json');

        if (!File::exists($jsonPath)) {
            $this->command?->error("File sumber tidak ditemukan: {$jsonPath}");
            return;
        }

        $this->command?->info("Membaca berkas JSON: {$jsonPath} ...");
        $rawJson = File::get($jsonPath);
        $data = json_decode($rawJson, true);

        if (!is_array($data)) {
            $this->command?->error("Format JSON tidak valid atau gagal didecode.");
            return;
        }

        $this->command?->info("Menyusun peta kategori induk (Level 1 - 6)...");
        $categoryMap = [];
        foreach ($data as $item) {
            $level = (int)($item['level'] ?? 0);
            if ($level >= 1 && $level <= 6 && !empty($item['kode'])) {
                $categoryMap[trim($item['kode'])] = trim($item['uraian'] ?? '');
            }
        }

        $now = now()->toDateTimeString();
        $batch = [];
        $batchSize = 500;
        $totalImported = 0;
        $seenCodes = [];

        $level7Count = 0;
        foreach ($data as $item) {
            if ((int)($item['level'] ?? 0) === 7) {
                $level7Count++;
            }
        }

        $this->command?->info("Memproses dan mengimpor {$level7Count} item Level 7 Permendagri 108...");

        foreach ($data as $item) {
            if ((int)($item['level'] ?? 0) !== 7) {
                continue;
            }

            $rawKode = trim($item['kode'] ?? '');
            $rawUraian = trim($item['uraian'] ?? '');

            if (empty($rawKode) || empty($rawUraian)) {
                continue;
            }

            // Tangani 16 pasang kode duplikat regulasi Permendagri 108
            // kode_permendagri tetap berisi kode asli regulasi
            // kode_barang diberikan postfix unik .1 untuk baris duplikat kedua
            if (!isset($seenCodes[$rawKode])) {
                $kodeBarang = $rawKode;
                $seenCodes[$rawKode] = 1;
            } else {
                $seenCodes[$rawKode]++;
                $kodeBarang = $rawKode . '.' . ($seenCodes[$rawKode] - 1);
            }

            $kodePermendagri = $rawKode;

            // Bentuk hierarki deskripsi kategori (misal Level 5 > Level 6)
            $parentDescriptions = [];
            $codeParts = explode('.', $rawKode);
            if (count($codeParts) >= 6) {
                $lvl5Code = implode('.', array_slice($codeParts, 0, 5));
                $lvl6Code = implode('.', array_slice($codeParts, 0, 6));

                if (isset($categoryMap[$lvl5Code])) {
                    $parentDescriptions[] = $categoryMap[$lvl5Code];
                }
                if (isset($categoryMap[$lvl6Code]) && !in_array($categoryMap[$lvl6Code], $parentDescriptions)) {
                    $parentDescriptions[] = $categoryMap[$lvl6Code];
                }
            }

            $deskripsi = !empty($parentDescriptions) ? implode(' > ', $parentDescriptions) : null;

            // Penentuan satuan default
            $satuan = 'Unit';
            if (str_starts_with($rawKode, '1.1.7.01.01.01.')) { // Bahan Bangunan
                $satuan = 'Unit';
            } elseif (str_starts_with($rawKode, '1.1.7.01.01.02.')) { // ATK / Kertas
                $satuan = 'Buah';
            } elseif (str_starts_with($rawKode, '1.3.2.05.')) { // Mebeuler
                $satuan = 'Unit';
            } elseif (str_starts_with($rawKode, '1.3.2.07.')) { // Alat Medis
                $satuan = 'Unit';
            } elseif (str_starts_with($rawKode, '1.3.2.10.')) { // Komputer & TI
                $satuan = 'Unit';
            }

            $batch[] = [
                'kode_barang' => $kodeBarang,
                'kode_permendagri' => $kodePermendagri,
                'nama_barang' => mb_substr($rawUraian, 0, 255),
                'satuan' => $satuan,
                'deskripsi' => $deskripsi,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= $batchSize) {
                DB::table('master_barangs')->upsert(
                    $batch,
                    ['kode_barang'],
                    ['kode_permendagri', 'nama_barang', 'satuan', 'deskripsi', 'is_active', 'updated_at']
                );
                $totalImported += count($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('master_barangs')->upsert(
                $batch,
                ['kode_barang'],
                ['kode_permendagri', 'nama_barang', 'satuan', 'deskripsi', 'is_active', 'updated_at']
            );
            $totalImported += count($batch);
        }

        $this->command?->info("Berhasil mengimpor/menyinkronkan {$totalImported} data Master Barang Permendagri No. 108 Tahun 2016.");
    }
}
