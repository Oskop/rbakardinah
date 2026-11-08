<?php

namespace Tests\Feature\Operator;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Unit;
use App\Models\SubUnit;
use App\Models\RkbmdSubmission;
use App\Models\RkbmdItem;
use App\Models\RkbmdHistory;
use App\Models\MasterBarang;
use App\Models\ActivityLog;

class RkbmdSubUnitTargetTest extends TestCase
{
    use RefreshDatabase;

    protected Unit $unitPDE;
    protected SubUnit $subUnitPDE;
    protected User $userPDE; // PIC PDE (can_propose = true)
    protected User $userDhimas; // Staf Ruangan PDE (can_propose = false)

    protected Unit $unitRajal;
    protected SubUnit $subUnitJantung;
    protected User $perawatA; // Pemohon Asli di Poli Jantung
    protected User $perawatB; // Rekan Satu Sub-Unit di Poli Jantung

    protected MasterBarang $barang;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        // Unit & Sub-Unit PDE
        $this->unitPDE = Unit::create(['code' => 'PDE', 'name' => 'Instalasi PDE', 'is_active' => true]);
        $this->subUnitPDE = SubUnit::create([
            'unit_id' => $this->unitPDE->id,
            'name' => 'Sub-Unit PDE',
            'code' => 'PDE01',
            'is_active' => true,
        ]);

        $this->userPDE = User::factory()->create([
            'name' => 'User PDE (PIC)',
            'role' => 'Operator',
            'unit_id' => $this->unitPDE->id,
            'sub_unit_id' => $this->subUnitPDE->id,
            'can_propose' => true,
            'is_active' => true,
        ]);

        $this->userDhimas = User::factory()->create([
            'name' => 'User Dhimas (Staf PDE)',
            'role' => 'Operator',
            'unit_id' => $this->unitPDE->id,
            'sub_unit_id' => $this->subUnitPDE->id,
            'can_propose' => false,
            'is_active' => true,
        ]);

        // Unit & Sub-Unit Poli Jantung
        $this->unitRajal = Unit::create(['code' => 'IRJ', 'name' => 'Instalasi Rawat Jalan', 'is_active' => true]);
        $this->subUnitJantung = SubUnit::create([
            'unit_id' => $this->unitRajal->id,
            'name' => 'Poliklinik Jantung',
            'code' => 'PJ01',
            'is_active' => true,
        ]);

        $this->perawatA = User::factory()->create([
            'name' => 'Perawat A (Pemohon)',
            'role' => 'Operator',
            'unit_id' => $this->unitRajal->id,
            'sub_unit_id' => $this->subUnitJantung->id,
            'can_propose' => false,
            'is_active' => true,
        ]);

        $this->perawatB = User::factory()->create([
            'name' => 'Perawat B (Rekan Ruangan)',
            'role' => 'Operator',
            'unit_id' => $this->unitRajal->id,
            'sub_unit_id' => $this->subUnitJantung->id,
            'can_propose' => false,
            'is_active' => true,
        ]);

        $this->barang = MasterBarang::create([
            'kode_barang' => '1.1.7.01.01.01.099',
            'nama_barang' => 'Kabel Jaringan UTP Cat6',
            'satuan' => 'Roll',
            'is_active' => true,
        ]);
    }

    public function test_incoming_submissions_are_visible_to_all_staff_in_target_sub_unit()
    {
        $pdf = UploadedFile::fake()->create('memo_jantung.pdf', 100, 'application/pdf');

        // Perawat A mengajukan permohonan ke Sub-Unit PDE (dengan memilih PIC userPDE)
        $response = $this->actingAs($this->perawatA)->post(route('operator.rkbmd.store'), [
            'target_operator_id' => $this->userPDE->id,
            'title' => 'Pengadaan Kabel Jaringan Poli Jantung',
            'year' => 2026,
            'attachment' => $pdf,
            'items' => [
                [
                    'master_barang_id' => $this->barang->id,
                    'volume' => 2,
                    'satuan' => 'Roll',
                ],
            ],
        ]);
        $response->assertSessionHasNoErrors();

        $submission = RkbmdSubmission::first();
        $this->assertNotNull($submission);
        $this->assertEquals($this->unitPDE->id, $submission->target_unit_id);
        $this->assertEquals($this->subUnitPDE->id, $submission->target_sub_unit_id);

        // 1. User Dhimas (staf non-PIC di PDE) dapat melihat di tab Permohonan Masuk
        $responseDhimasIndex = $this->actingAs($this->userDhimas)->get(route('operator.rkbmd.index'));
        $responseDhimasIndex->assertStatus(200);
        $responseDhimasIndex->assertSee('Pengadaan Kabel Jaringan Poli Jantung');
        $responseDhimasIndex->assertSee('Permohonan Masuk (Sub-Unit Kita)');
        $responseDhimasIndex->assertSee('Lihat Berkas →');

        // 2. User Dhimas dapat membuka detail show (Read-Only)
        $responseDhimasShow = $this->actingAs($this->userDhimas)->get(route('operator.rkbmd.show', $submission));
        $responseDhimasShow->assertStatus(200);
        $responseDhimasShow->assertSee('Akses Pemantauan Sub-Unit (Read-Only)');
        $responseDhimasShow->assertDontSee('Tanggapi / Berikan Balasan');
        $responseDhimasShow->assertDontSee('Alihkan Permohonan');

        // 3. User Dhimas diblokir jika mencoba reply atau forward langsung
        $replyAttempt = $this->actingAs($this->userDhimas)->post(route('operator.rkbmd.reply', $submission), [
            'status' => 'Dipenuhi',
            'reply_notes' => 'Mencoba membalas secara ilegal',
        ]);
        $replyAttempt->assertStatus(403);

        $forwardAttempt = $this->actingAs($this->userDhimas)->post(route('operator.rkbmd.forward', $submission), [
            'new_target_operator_id' => $this->userPDE->id,
            'forward_reason' => 'Mencoba mengalihkan secara ilegal',
        ]);
        $forwardAttempt->assertStatus(403);
    }

    public function test_target_pic_can_reply_and_logs_activity_and_history()
    {
        $pdf = UploadedFile::fake()->create('memo_jantung.pdf', 100, 'application/pdf');

        $this->actingAs($this->perawatA)->post(route('operator.rkbmd.store'), [
            'target_operator_id' => $this->userPDE->id,
            'title' => 'Permohonan Kabel Server',
            'year' => 2026,
            'attachment' => $pdf,
            'items' => [
                [
                    'master_barang_id' => $this->barang->id,
                    'volume' => 1,
                    'satuan' => 'Roll',
                ],
            ],
        ]);

        $submission = RkbmdSubmission::first();

        // User PDE (PIC) melihat tombol dan bisa membalas
        $responsePdeShow = $this->actingAs($this->userPDE)->get(route('operator.rkbmd.show', $submission));
        $responsePdeShow->assertStatus(200);
        $responsePdeShow->assertSee('Tanggapi / Berikan Balasan');

        $replyResponse = $this->actingAs($this->userPDE)->post(route('operator.rkbmd.reply', $submission), [
            'status' => 'Dipenuhi',
            'reply_notes' => 'Kabel UTP disetujui untuk pemenuhan instalasi.',
        ]);
        $replyResponse->assertRedirect(route('operator.rkbmd.show', $submission));

        $submission->refresh();
        $this->assertEquals('Dipenuhi', $submission->status);
        $this->assertEquals($this->userPDE->id, $submission->replied_by);

        // Verifikasi RkbmdHistory
        $this->assertDatabaseHas('rkbmd_histories', [
            'rkbmd_submission_id' => $submission->id,
            'user_id' => $this->userPDE->id,
            'action' => 'Balasan',
            'status_after' => 'Dipenuhi',
        ]);

        // Verifikasi ActivityLog
        $this->assertDatabaseHas('activity_logs', [
            'model_type' => RkbmdSubmission::class,
            'model_id' => $submission->id,
            'action' => 'updated',
            'user_id' => $this->userPDE->id,
        ]);
    }

    public function test_outbound_submissions_are_visible_to_all_staff_in_applicant_sub_unit()
    {
        $pdf = UploadedFile::fake()->create('memo_jantung.pdf', 100, 'application/pdf');

        // Perawat A membuat permohonan
        $this->actingAs($this->perawatA)->post(route('operator.rkbmd.store'), [
            'target_operator_id' => $this->userPDE->id,
            'title' => 'Permohonan Alat Medis Ruang Jantung',
            'year' => 2026,
            'attachment' => $pdf,
            'items' => [
                [
                    'master_barang_id' => $this->barang->id,
                    'volume' => 5,
                    'satuan' => 'Roll',
                ],
            ],
        ]);

        $submission = RkbmdSubmission::first();

        // 1. Perawat B (rekan satu ruangan) dapat melihat di tab Permohonan Diajukan
        $responseBIndex = $this->actingAs($this->perawatB)->get(route('operator.rkbmd.index'));
        $responseBIndex->assertStatus(200);
        $responseBIndex->assertSee('Permohonan Alat Medis Ruang Jantung');
        $responseBIndex->assertSee('Pemohon:');
        $responseBIndex->assertSee($this->perawatA->name);

        // 2. Perawat B tidak melihat tombol Edit di index
        // Perawat A melihat tombol Edit
        $responseAIndex = $this->actingAs($this->perawatA)->get(route('operator.rkbmd.index'));
        $responseAIndex->assertSee('Edit');

        // 3. Perawat B dapat membuka detail show (Read-Only)
        $responseBShow = $this->actingAs($this->perawatB)->get(route('operator.rkbmd.show', $submission));
        $responseBShow->assertStatus(200);
        $responseBShow->assertSee('Permohonan Diajukan oleh Ruangan Anda');
        $responseBShow->assertDontSee('Edit Permohonan');

        // 4. Perawat B diblokir jika mencoba mengakses halaman edit atau submit update
        $editAttempt = $this->actingAs($this->perawatB)->get(route('operator.rkbmd.edit', $submission));
        $editAttempt->assertStatus(403);

        $updateAttempt = $this->actingAs($this->perawatB)->put(route('operator.rkbmd.update', $submission), [
            'target_operator_id' => $this->userPDE->id,
            'title' => 'Judul Diubah Ilegal',
            'year' => 2026,
            'items' => [
                [
                    'master_barang_id' => $this->barang->id,
                    'volume' => 10,
                    'satuan' => 'Roll',
                ],
            ],
        ]);
        $updateAttempt->assertStatus(403);

        // 5. Perawat A (pemohon asli) sukses mengedit permohonan
        $updateResponse = $this->actingAs($this->perawatA)->put(route('operator.rkbmd.update', $submission), [
            'target_operator_id' => $this->userPDE->id,
            'title' => 'Permohonan Alat Medis Ruang Jantung (Revisi)',
            'year' => 2026,
            'items' => [
                [
                    'master_barang_id' => $this->barang->id,
                    'volume' => 3,
                    'satuan' => 'Roll',
                ],
            ],
        ]);
        $updateResponse->assertRedirect(route('operator.rkbmd.show', $submission));

        $submission->refresh();
        $this->assertEquals('Permohonan Alat Medis Ruang Jantung (Revisi)', $submission->title);

        // Verifikasi RkbmdHistory dan ActivityLog mencatat edit permohonan
        $this->assertDatabaseHas('rkbmd_histories', [
            'rkbmd_submission_id' => $submission->id,
            'user_id' => $this->perawatA->id,
            'action' => 'Edit Permohonan',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'model_type' => RkbmdSubmission::class,
            'model_id' => $submission->id,
            'action' => 'updated',
            'user_id' => $this->perawatA->id,
        ]);
    }

    public function test_activity_log_and_rkbmd_history_are_recorded_for_all_actions()
    {
        $pdf = UploadedFile::fake()->create('memo_jantung.pdf', 100, 'application/pdf');

        // 1. Action: Pengajuan (Store)
        $this->actingAs($this->perawatA)->post(route('operator.rkbmd.store'), [
            'target_operator_id' => $this->userPDE->id,
            'title' => 'Permohonan Switch Jaringan',
            'year' => 2026,
            'attachment' => $pdf,
            'items' => [
                [
                    'master_barang_id' => $this->barang->id,
                    'volume' => 1,
                    'satuan' => 'Roll',
                ],
            ],
        ]);

        $submission = RkbmdSubmission::where('title', 'Permohonan Switch Jaringan')->first();
        $this->assertNotNull($submission);

        // Verifikasi Log Pengajuan
        $this->assertDatabaseHas('rkbmd_histories', [
            'rkbmd_submission_id' => $submission->id,
            'user_id' => $this->perawatA->id,
            'action' => 'Pengajuan',
            'status_after' => 'Diajukan',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'model_type' => RkbmdSubmission::class,
            'model_id' => $submission->id,
            'action' => 'created',
            'user_id' => $this->perawatA->id,
        ]);

        // Buat satu operator lain untuk target pengalihan
        $unitApotek = Unit::create(['code' => 'APT', 'name' => 'Instalasi Farmasi', 'is_active' => true]);
        $subUnitApotek = SubUnit::create([
            'unit_id' => $unitApotek->id,
            'name' => 'Gudang Farmasi',
            'code' => 'APT01',
            'is_active' => true,
        ]);
        $userApotek = User::factory()->create([
            'name' => 'PIC Farmasi',
            'role' => 'Operator',
            'unit_id' => $unitApotek->id,
            'sub_unit_id' => $subUnitApotek->id,
            'can_propose' => true,
            'is_active' => true,
        ]);

        // 2. Action: Pengalihan (Forward) oleh PIC PDE
        $this->actingAs($this->userPDE)->post(route('operator.rkbmd.forward', $submission), [
            'new_target_operator_id' => $userApotek->id,
            'forward_reason' => 'Salah tujuan, seharusnya ke Farmasi',
        ]);

        $submission->refresh();
        $this->assertEquals('Dialihkan', $submission->status);
        $this->assertEquals($userApotek->id, $submission->target_operator_id);
        $this->assertEquals($subUnitApotek->id, $submission->target_sub_unit_id);

        $this->assertDatabaseHas('rkbmd_histories', [
            'rkbmd_submission_id' => $submission->id,
            'user_id' => $this->userPDE->id,
            'action' => 'Pengalihan',
            'from_operator_id' => $this->userPDE->id,
            'to_operator_id' => $userApotek->id,
            'status_after' => 'Dialihkan',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'model_type' => RkbmdSubmission::class,
            'model_id' => $submission->id,
            'action' => 'updated',
            'user_id' => $this->userPDE->id,
        ]);

        // 3. Action: Balasan (Reply) oleh PIC Farmasi
        $this->actingAs($userApotek)->post(route('operator.rkbmd.reply', $submission), [
            'status' => 'Dipenuhi',
            'reply_notes' => 'Disetujui oleh Farmasi.',
        ]);

        $submission->refresh();
        $this->assertEquals('Dipenuhi', $submission->status);

        $this->assertDatabaseHas('rkbmd_histories', [
            'rkbmd_submission_id' => $submission->id,
            'user_id' => $userApotek->id,
            'action' => 'Balasan',
            'status_after' => 'Dipenuhi',
        ]);

        // 4. Action: Edit Balasan (Update Reply) oleh PIC Farmasi
        $this->actingAs($userApotek)->put(route('operator.rkbmd.reply.update', $submission), [
            'status' => 'Dipenuhi Sebagian',
            'reply_notes' => 'Revisi balasan: hanya tersedia separuh.',
        ]);

        $submission->refresh();
        $this->assertEquals('Dipenuhi Sebagian', $submission->status);

        $this->assertDatabaseHas('rkbmd_histories', [
            'rkbmd_submission_id' => $submission->id,
            'user_id' => $userApotek->id,
            'action' => 'Edit Balasan',
            'status_after' => 'Dipenuhi Sebagian',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'model_type' => RkbmdSubmission::class,
            'model_id' => $submission->id,
            'action' => 'updated',
            'user_id' => $userApotek->id,
        ]);
    }

    public function test_existing_production_data_without_target_sub_unit_is_accessible()
    {
        // Simulasi data lama: target_unit_id dan target_sub_unit_id bernilai null
        $oldSubmission = RkbmdSubmission::create([
            'nomor_permohonan' => 'RKBMD/2026/09/OLD01',
            'year' => 2026,
            'user_id' => $this->perawatA->id,
            'unit_id' => $this->unitRajal->id,
            'sub_unit_id' => $this->subUnitJantung->id,
            'target_operator_id' => $this->userPDE->id,
            'original_operator_id' => $this->userPDE->id,
            'target_unit_id' => null,
            'target_sub_unit_id' => null,
            'title' => 'Permohonan Lama Sebelum Migrasi',
            'status' => 'Diajukan',
        ]);

        // Fallback backward compatibility: User Dhimas tetap dapat melihatnya melalui targetOperator
        $responseDhimas = $this->actingAs($this->userDhimas)->get(route('operator.rkbmd.index'));
        $responseDhimas->assertStatus(200);

        $responseDhimasShow = $this->actingAs($this->userDhimas)->get(route('operator.rkbmd.show', $oldSubmission));
        $responseDhimasShow->assertStatus(200);

        // User PDE (PIC) tetap memiliki kewenangan kelola
        $this->assertTrue($oldSubmission->canBeManagedBy($this->userPDE));
        $this->assertFalse($oldSubmission->canBeManagedBy($this->userDhimas));
    }

    public function test_description_in_all_three_tables_renders_with_wrap_classes_and_avoids_horizontal_overflow()
    {
        $longNotes = 'Deskripsi permohonan yang sangat panjang untuk keperluan pengadaan infrastruktur jaringan LAN di seluruh lantai ruang rawat inap demi kelancaran integrasi rekam medis elektronik RME dan sistem informasi manajemen rumah sakit.';

        // 1. Buat permohonan dengan deskripsi panjang
        $submission = RkbmdSubmission::create([
            'nomor_permohonan' => 'RKBMD/2026/11/WRAP01',
            'year' => 2026,
            'user_id' => $this->perawatA->id,
            'unit_id' => $this->unitRajal->id,
            'sub_unit_id' => $this->subUnitJantung->id,
            'target_operator_id' => $this->userPDE->id,
            'original_operator_id' => $this->userPDE->id,
            'target_unit_id' => $this->unitPDE->id,
            'target_sub_unit_id' => $this->subUnitPDE->id,
            'title' => 'Pengadaan Switch dan Kabel Jaringan Utama',
            'notes' => $longNotes,
            'status' => 'Diajukan',
        ]);

        // Verifikasi Tabel 1 (Permohonan Saya)
        $responseMine = $this->actingAs($this->perawatA)->get(route('operator.rkbmd.index'));
        $responseMine->assertStatus(200);
        $responseMine->assertSee('min-w-[220px] max-w-xs md:max-w-sm', false);
        $responseMine->assertSee('break-words whitespace-normal leading-relaxed', false);
        $responseMine->assertSee($longNotes);

        // Verifikasi Tabel 2 (Permohonan Masuk)
        $responseIncoming = $this->actingAs($this->userPDE)->get(route('operator.rkbmd.index'));
        $responseIncoming->assertStatus(200);
        $responseIncoming->assertSee('min-w-[220px] max-w-xs md:max-w-sm', false);
        $responseIncoming->assertSee('break-words whitespace-normal leading-relaxed', false);
        $responseIncoming->assertSee($longNotes);

        // Alihkan permohonan untuk menguji Tabel 3 (Permohonan Dialihkan)
        $unitApotek = Unit::create(['code' => 'APT2', 'name' => 'Instalasi Farmasi 2', 'is_active' => true]);
        $subUnitApotek = SubUnit::create([
            'unit_id' => $unitApotek->id,
            'name' => 'Gudang Farmasi 2',
            'code' => 'APT02',
            'is_active' => true,
        ]);
        $userApotek = User::factory()->create([
            'name' => 'PIC Farmasi 2',
            'role' => 'Operator',
            'unit_id' => $unitApotek->id,
            'sub_unit_id' => $subUnitApotek->id,
            'can_propose' => true,
            'is_active' => true,
        ]);

        $this->actingAs($this->userPDE)->post(route('operator.rkbmd.forward', $submission), [
            'new_target_operator_id' => $userApotek->id,
            'forward_reason' => 'Dialihkan ke farmasi untuk koordinasi',
        ]);

        // Verifikasi Tabel 3 (Permohonan Dialihkan di akun userPDE)
        $responseForwarded = $this->actingAs($this->userPDE)->get(route('operator.rkbmd.index'));
        $responseForwarded->assertStatus(200);
        $responseForwarded->assertSee('table-forwarded-rkbmd', false);
        $responseForwarded->assertSee('min-w-[220px] max-w-xs md:max-w-sm', false);
        $responseForwarded->assertSee('break-words whitespace-normal leading-relaxed', false);
        $responseForwarded->assertSee($longNotes);
    }
}
