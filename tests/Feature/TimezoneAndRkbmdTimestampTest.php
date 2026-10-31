<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Unit;
use App\Models\SubUnit;
use App\Models\RkbmdSubmission;
use App\Models\RkbmdItem;
use App\Models\RkbmdHistory;
use App\Models\MasterBarang;
use Carbon\Carbon;

class TimezoneAndRkbmdTimestampTest extends TestCase
{
    use RefreshDatabase;

    protected User $operator1;
    protected User $operator2;
    protected Unit $unit1;
    protected Unit $unit2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unit1 = Unit::create(['code' => 'UP1', 'name' => 'Unit Pelayanan 1', 'is_active' => true]);
        $this->unit2 = Unit::create(['code' => 'UF1', 'name' => 'Unit Farmasi', 'is_active' => true]);

        $this->operator1 = User::factory()->create([
            'role' => 'Operator',
            'unit_id' => $this->unit1->id,
            'is_active' => true,
        ]);

        $this->operator2 = User::factory()->create([
            'role' => 'Operator',
            'unit_id' => $this->unit2->id,
            'is_active' => true,
        ]);
    }

    public function test_application_timezone_is_set_to_asia_jakarta()
    {
        $this->assertEquals('Asia/Jakarta', config('app.timezone'));
        $this->assertEquals('Asia/Jakarta', now()->timezoneName);
    }

    public function test_new_rkbmd_submission_stores_created_at_in_wib_timezone()
    {
        // Tetapkan waktu simulasi pada jam 10:35 WIB
        $simulatedTime = Carbon::create(2026, 10, 31, 10, 35, 0, 'Asia/Jakarta');
        Carbon::setTestNow($simulatedTime);

        $submission = RkbmdSubmission::create([
            'nomor_permohonan' => 'RKBMD/2026/10/9999',
            'year' => 2026,
            'user_id' => $this->operator1->id,
            'unit_id' => $this->unit1->id,
            'target_operator_id' => $this->operator2->id,
            'original_operator_id' => $this->operator1->id,
            'title' => 'Uji Waktu WIB',
            'status' => 'Diajukan',
        ]);

        $this->assertEquals('2026-10-31 10:35:00', $submission->created_at->format('Y-m-d H:i:s'));
        $this->assertEquals('Asia/Jakarta', $submission->created_at->timezoneName);

        Carbon::setTestNow(); // reset
    }

    public function test_rkbmd_views_render_wib_suffix_and_local_time()
    {
        $testTime = Carbon::create(2026, 10, 31, 11, 15, 0, 'Asia/Jakarta');
        Carbon::setTestNow($testTime);

        $submission = RkbmdSubmission::create([
            'nomor_permohonan' => 'RKBMD/2026/10/7777',
            'year' => 2026,
            'user_id' => $this->operator1->id,
            'unit_id' => $this->unit1->id,
            'target_operator_id' => $this->operator2->id,
            'original_operator_id' => $this->operator1->id,
            'title' => 'Pengadaan Alat Medis',
            'status' => 'Diajukan',
        ]);

        $responseIndex = $this->actingAs($this->operator1)->get(route('rkbmd.index'));
        $responseIndex->assertStatus(200);
        $responseIndex->assertSee('31/10/2026 11:15 WIB');

        $responseShow = $this->actingAs($this->operator1)->get(route('rkbmd.show', $submission));
        $responseShow->assertStatus(200);
        $responseShow->assertSee('11:15 WIB');
        $responseShow->assertSee($submission->created_at->format('d M Y, H:i') . ' WIB');

        Carbon::setTestNow();
    }

    public function test_artisan_command_fixes_utc_timestamps_to_wib()
    {
        // Simulasi record yang dibuat dalam UTC (jam 02:35)
        $sub = RkbmdSubmission::create([
            'nomor_permohonan' => 'RKBMD/2026/10/8888',
            'year' => 2026,
            'user_id' => $this->operator1->id,
            'unit_id' => $this->unit1->id,
            'target_operator_id' => $this->operator2->id,
            'original_operator_id' => $this->operator1->id,
            'title' => 'Permohonan Waktu UTC Lama',
            'status' => 'Dipenuhi',
            'created_at' => '2026-10-31 02:35:00',
            'updated_at' => '2026-10-31 02:35:00',
            'replied_at' => '2026-10-31 03:00:00',
        ]);

        $barang = MasterBarang::create([
            'kode_barang' => '1.1.7.01.01.01.001',
            'nama_barang' => 'Kasa Steril',
            'satuan' => 'Box',
            'is_active' => true,
        ]);

        $item = RkbmdItem::create([
            'rkbmd_submission_id' => $sub->id,
            'master_barang_id' => $barang->id,
            'volume' => 10,
            'satuan' => 'Box',
            'created_at' => '2026-10-31 02:35:00',
            'updated_at' => '2026-10-31 02:35:00',
        ]);

        $history = RkbmdHistory::create([
            'rkbmd_submission_id' => $sub->id,
            'user_id' => $this->operator1->id,
            'action' => 'Pengajuan',
            'notes' => 'Pengajuan awal',
        ]);

        \Illuminate\Support\Facades\DB::table('rkbmd_submissions')->where('id', $sub->id)->update([
            'created_at' => '2026-10-31 02:35:00',
            'updated_at' => '2026-10-31 02:35:00',
            'replied_at' => '2026-10-31 03:00:00',
        ]);
        \Illuminate\Support\Facades\DB::table('rkbmd_items')->where('id', $item->id)->update([
            'created_at' => '2026-10-31 02:35:00',
            'updated_at' => '2026-10-31 02:35:00',
        ]);
        \Illuminate\Support\Facades\DB::table('rkbmd_histories')->where('id', $history->id)->update([
            'created_at' => '2026-10-31 02:35:00',
            'updated_at' => '2026-10-31 02:35:00',
        ]);

        // 1. Dry run tidak mengubah apapun
        $this->artisan('rkbmd:fix-utc-timestamps --dry-run')
            ->expectsOutputToContain('Mode dry-run selesai')
            ->assertExitCode(0);

        $sub->refresh();
        $this->assertEquals('2026-10-31 02:35:00', $sub->created_at->format('Y-m-d H:i:s'));

        // 2. Eksekusi dengan --force
        $this->artisan('rkbmd:fix-utc-timestamps --force')
            ->expectsOutputToContain('SUKSES: Penyesuaian timestamp berhasil disimpan!')
            ->assertExitCode(0);

        // Verifikasi +7 jam telah bertambah (02:35:00 + 7 jam = 09:35:00)
        $sub->refresh();
        $item->refresh();
        $history->refresh();

        $this->assertEquals('2026-10-31 09:35:00', $sub->created_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-10-31 10:00:00', $sub->replied_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-10-31 09:35:00', $item->created_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-10-31 09:35:00', $history->created_at->format('Y-m-d H:i:s'));
    }
}
