<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ActivityLog;

class FixRkbmdUtcTimestamps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rkbmd:fix-utc-timestamps 
                            {--hours=7 : Jumlah jam yang ditambahkan (default: 7 untuk konversi UTC ke WIB)}
                            {--before= : Batas tanggal/waktu maksimum (Y-m-d H:i:s) untuk record yang akan diupdate}
                            {--force : Jalankan update database tanpa meminta konfirmasi interaktif}
                            {--dry-run : Tampilkan pratinjau record yang akan diupdate tanpa mengubah database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menyesuaikan timestamps record RKBMD yang terlanjur tersimpan dalam timezone UTC menjadi WIB (+7 jam).';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = (int) $this->option('hours');
        $before = $this->option('before');
        $isDryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $this->info("=== Penyesuaian Timestamp RKBMD (UTC ke WIB) ===");
        $this->line("Pergeseran waktu : +{$hours} jam");
        if ($before) {
            $this->line("Filter record    : created_at <= {$before}");
        }
        $this->line("Mode             : " . ($isDryRun ? '<comment>DRY-RUN (Hanya Pratinjau)</comment>' : '<info>EKSEKUSI DATABASE</info>'));
        $this->newLine();

        // 1. Ambil data rkbmd_submissions
        $submissionsQuery = DB::table('rkbmd_submissions');
        if ($before) {
            $submissionsQuery->where('created_at', '<=', $before);
        }
        $submissions = $submissionsQuery->get();

        if ($submissions->isEmpty()) {
            $this->warn("Tidak ditemukan record rkbmd_submissions yang memenuhi kriteria.");
            return 0;
        }

        $this->line("Ditemukan <info>{$submissions->count()}</info> record rkbmd_submissions.");

        // Tabel preview
        $previewData = [];
        foreach ($submissions as $sub) {
            $origCreated = $sub->created_at;
            $newCreated = Carbon::parse($origCreated)->addHours($hours)->toDateTimeString();
            $origReplied = $sub->replied_at;
            $newReplied = $origReplied ? Carbon::parse($origReplied)->addHours($hours)->toDateTimeString() : '-';

            $previewData[] = [
                $sub->id,
                $sub->nomor_permohonan,
                $origCreated,
                $newCreated,
                $origReplied ?? '-',
                $newReplied,
            ];
        }

        $this->table(
            ['ID', 'Nomor RKBMD', 'Created (Asli)', 'Created (+7 Jam)', 'Replied (Asli)', 'Replied (+7 Jam)'],
            $previewData
        );

        if ($isDryRun) {
            $this->info("Mode dry-run selesai. Tidak ada perubahan yang disimpan ke database.");
            return 0;
        }

        if (!$force && !$this->confirm("Apakah Anda yakin ingin memperbarui timestamps record di atas?", false)) {
            $this->warn("Operasi dibatalkan oleh pengguna.");
            return 1;
        }

        // Lakukan eksekusi dalam DB transaction
        DB::beginTransaction();
        try {
            $submissionIds = $submissions->pluck('id')->toArray();
            $updatedSubmissionsCount = 0;

            foreach ($submissions as $sub) {
                $updateData = [
                    'created_at' => Carbon::parse($sub->created_at)->addHours($hours)->toDateTimeString(),
                    'updated_at' => Carbon::parse($sub->updated_at)->addHours($hours)->toDateTimeString(),
                ];
                if ($sub->replied_at) {
                    $updateData['replied_at'] = Carbon::parse($sub->replied_at)->addHours($hours)->toDateTimeString();
                }

                DB::table('rkbmd_submissions')->where('id', $sub->id)->update($updateData);
                $updatedSubmissionsCount++;
            }

            // 2. Ambil dan perbarui rkbmd_items terkait
            $items = DB::table('rkbmd_items')->whereIn('rkbmd_submission_id', $submissionIds)->get();
            $updatedItemsCount = 0;
            foreach ($items as $item) {
                DB::table('rkbmd_items')->where('id', $item->id)->update([
                    'created_at' => Carbon::parse($item->created_at)->addHours($hours)->toDateTimeString(),
                    'updated_at' => Carbon::parse($item->updated_at)->addHours($hours)->toDateTimeString(),
                ]);
                $updatedItemsCount++;
            }

            // 3. Ambil dan perbarui rkbmd_histories terkait
            $histories = DB::table('rkbmd_histories')->whereIn('rkbmd_submission_id', $submissionIds)->get();
            $updatedHistoriesCount = 0;
            foreach ($histories as $history) {
                DB::table('rkbmd_histories')->where('id', $history->id)->update([
                    'created_at' => Carbon::parse($history->created_at)->addHours($hours)->toDateTimeString(),
                    'updated_at' => Carbon::parse($history->updated_at)->addHours($hours)->toDateTimeString(),
                ]);
                $updatedHistoriesCount++;
            }

            // Catat log aktivitas jika tabel activity_logs tersedia
            try {
                ActivityLog::create([
                    'user_id' => 1,
                    'user_name' => 'CLI Artisan',
                    'user_role' => 'Administrator',
                    'action' => 'updated',
                    'model_type' => 'App\Models\RkbmdSubmission',
                    'model_id' => 0,
                    'description' => "Menyesuaikan timezone timestamp RKBMD (+{$hours} jam) untuk {$updatedSubmissionsCount} permohonan, {$updatedItemsCount} item, dan {$updatedHistoriesCount} riwayat.",
                ]);
            } catch (\Throwable $e) {
                // Abaikan jika log tidak krusial
            }

            DB::commit();

            $this->newLine();
            $this->info("SUKSES: Penyesuaian timestamp berhasil disimpan!");
            $this->line("- RKBMD Submissions : <info>{$updatedSubmissionsCount}</info> record");
            $this->line("- RKBMD Items       : <info>{$updatedItemsCount}</info> record");
            $this->line("- RKBMD Histories   : <info>{$updatedHistoriesCount}</info> record");

            return 0;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Gagal melakukan penyesuaian timestamp: " . $e->getMessage());
            return 1;
        }
    }
}

