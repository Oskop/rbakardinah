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
        $json26Path = base_path('data/permendagri_108_2016/tabel_2_6_sub_sub_rincian_objek.json');
        $json16Path = base_path('data/permendagri_108_2016/permendagri_108_master_level1_6.json');

        if (!File::exists($json26Path)) {
            $this->command?->error("File sumber Level 7 tidak ditemukan: {$json26Path}");
            return;
        }

        // 1. Baca data bersih Level 1 s.d. Level 6 dari permendagri_108_master_level1_6.json
        $lvl5Map = [];
        $lvl6Map = [];
        if (File::exists($json16Path)) {
            $this->command?->info("Membaca data referensi kategori bersih (Level 5 & 6)...");
            $master16Data = json_decode(File::get($json16Path), true);
            if (is_array($master16Data)) {
                foreach ($master16Data as $row) {
                    $lvl = (int)($row['level'] ?? 0);
                    $kd = trim($row['kode'] ?? '');
                    $ur = trim($row['uraian'] ?? '');
                    if ($lvl === 5 && !empty($kd)) {
                        $lvl5Map[$kd] = $ur;
                    } elseif ($lvl === 6 && !empty($kd)) {
                        $lvl6Map[$kd] = $ur;
                    }
                }
            }
        }

        // 2. Baca data tabel 2.6 untuk Level 7 dan fallback kategori
        $this->command?->info("Membaca berkas JSON Level 7: {$json26Path} ...");
        $rawJson = File::get($json26Path);
        $data = json_decode($rawJson, true);

        if (!is_array($data)) {
            $this->command?->error("Format JSON tidak valid atau gagal didecode.");
            return;
        }

        $fallbackCategoryMap = [];
        foreach ($data as $item) {
            $level = (int)($item['level'] ?? 0);
            if ($level >= 1 && $level <= 6 && !empty($item['kode'])) {
                $fallbackCategoryMap[trim($item['kode'])] = trim($item['uraian'] ?? '');
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

        $this->command?->info("Memproses dan mengimpor {$level7Count} item Level 7 Permendagri 108 dengan kategori Level 5 & 6...");

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
            if (!isset($seenCodes[$rawKode])) {
                $kodeBarang = $rawKode;
                $seenCodes[$rawKode] = 1;
            } else {
                $seenCodes[$rawKode]++;
                $kodeBarang = $rawKode . '.' . ($seenCodes[$rawKode] - 1);
            }

            $kodePermendagri = $rawKode;

            // Ekstrak kode Level 5 (Rincian Objek) dan Level 6 (Sub Rincian Objek)
            $codeParts = explode('.', $rawKode);
            $lvl5Code = count($codeParts) >= 5 ? implode('.', array_slice($codeParts, 0, 5)) : null;
            $lvl6Code = count($codeParts) >= 6 ? implode('.', array_slice($codeParts, 0, 6)) : null;

            // Nama Level 5 & 6 diambil dari sumber bersih (100% bebas typo OCR)
            $namaLvl5 = ($lvl5Code && isset($lvl5Map[$lvl5Code])) 
                ? $lvl5Map[$lvl5Code] 
                : ($fallbackCategoryMap[$lvl5Code] ?? null);

            $namaLvl6 = ($lvl6Code && isset($lvl6Map[$lvl6Code])) 
                ? $lvl6Map[$lvl6Code] 
                : ($fallbackCategoryMap[$lvl6Code] ?? null);

            // Kolom deskripsi bersih format: "NAMA LEVEL 5 > NAMA LEVEL 6"
            if ($namaLvl5 && $namaLvl6) {
                $deskripsi = "{$namaLvl5} > {$namaLvl6}";
            } elseif ($namaLvl5) {
                $deskripsi = $namaLvl5;
            } elseif ($namaLvl6) {
                $deskripsi = $namaLvl6;
            } else {
                $deskripsi = null;
            }

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
                'kode_level5' => $lvl5Code,
                'nama_level5' => $namaLvl5 ? mb_substr($namaLvl5, 0, 255) : null,
                'kode_level6' => $lvl6Code,
                'nama_level6' => $namaLvl6 ? mb_substr($namaLvl6, 0, 255) : null,
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
                    [
                        'kode_permendagri',
                        'kode_level5',
                        'nama_level5',
                        'kode_level6',
                        'nama_level6',
                        'nama_barang',
                        'satuan',
                        'deskripsi',
                        'is_active',
                        'updated_at'
                    ]
                );
                $totalImported += count($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('master_barangs')->upsert(
                $batch,
                ['kode_barang'],
                [
                    'kode_permendagri',
                    'kode_level5',
                    'nama_level5',
                    'kode_level6',
                    'nama_level6',
                    'nama_barang',
                    'satuan',
                    'deskripsi',
                    'is_active',
                    'updated_at'
                ]
            );
            $totalImported += count($batch);
        }

        // Tangani update untuk 3 data sampel RS awal jika ada
        DB::table('master_barangs')->where('kode_barang', '1.3.2.07.03.02.001')->update([
            'kode_level5' => '1.3.2.07.03',
            'nama_level5' => 'ALAT KEDOKTERAN LAINNYA',
            'kode_level6' => '1.3.2.07.03.02',
            'nama_level6' => 'ALAT PENGHISAP LENDIR / SUCTION PUMP',
            'deskripsi' => 'ALAT KEDOKTERAN LAINNYA > ALAT PENGHISAP LENDIR / SUCTION PUMP',
            'updated_at' => $now,
        ]);

        DB::table('master_barangs')->where('kode_barang', '1.3.2.07.04.01.001')->update([
            'kode_level5' => '1.3.2.07.04',
            'nama_level5' => 'PERALATAN RUMAH SAKIT',
            'kode_level6' => '1.3.2.07.04.01',
            'nama_level6' => 'TEMPAT TIDUR PASIEN (HOSPITAL BED)',
            'deskripsi' => 'PERALATAN RUMAH SAKIT > TEMPAT TIDUR PASIEN (HOSPITAL BED)',
            'updated_at' => $now,
        ]);

        DB::table('master_barangs')->where('kode_barang', '1.3.2.07.04.02.001')->update([
            'kode_level5' => '1.3.2.07.04',
            'nama_level5' => 'PERALATAN RUMAH SAKIT',
            'kode_level6' => '1.3.2.07.04.02',
            'nama_level6' => 'KURSI RODA PASIEN',
            'deskripsi' => 'PERALATAN RUMAH SAKIT > KURSI RODA PASIEN',
            'updated_at' => $now,
        ]);

        $this->command?->info("✓ Berhasil mengimpor/menyinkronkan {$totalImported} data Master Barang Permendagri No. 108 Tahun 2016 lengkap dengan Level 5 & 6.");
    }
}
