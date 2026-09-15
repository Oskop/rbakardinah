<?php

namespace Tests\Feature\Operator;

use App\Models\ActivityLog;
use App\Models\MasterBarang;
use App\Models\RkbmdHistory;
use App\Models\RkbmdSubmission;
use App\Models\SubUnit;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RkbmdTest extends TestCase
{
    use RefreshDatabase;

    protected Unit $unitA;
    protected Unit $unitB;
    protected SubUnit $subUnitPoli;
    protected User $pemohon;
    protected User $proposerA;
    protected User $proposerB;
    protected User $admin;
    protected MasterBarang $barangAC;
    protected MasterBarang $barangMeja;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->unitA = Unit::create(['code' => 'U01', 'name' => 'Bagian Rawat Jalan']);
        $this->unitB = Unit::create(['code' => 'U02', 'name' => 'Bagian Sarana Prasarana']);

        $this->subUnitPoli = SubUnit::create([
            'unit_id' => $this->unitA->id,
            'name' => 'Poliklinik Jantung',
            'code' => 'PJ01',
            'type' => 'Pelayanan',
            'is_active' => true,
        ]);

        // Pemohon: Operator Non-Pengusul (Viewer) di Poli Jantung
        $this->pemohon = User::create([
            'name' => 'Perawat Poli Jantung',
            'email' => 'perawat_jantung@test.com',
            'password' => bcrypt('password'),
            'role' => 'Operator',
            'unit_id' => $this->unitA->id,
            'sub_unit_id' => $this->subUnitPoli->id,
            'can_propose' => false,
        ]);

        // Proposer A: Operator Pengusul RBA Rawat Jalan
        $this->proposerA = User::create([
            'name' => 'PIC RBA Rawat Jalan',
            'email' => 'pic_rajal@test.com',
            'password' => bcrypt('password'),
            'role' => 'Operator',
            'unit_id' => $this->unitA->id,
            'can_propose' => true,
        ]);

        // Proposer B: Operator Pengusul RBA Sarpras
        $this->proposerB = User::create([
            'name' => 'PIC RBA Sarpras',
            'email' => 'pic_sarpras@test.com',
            'password' => bcrypt('password'),
            'role' => 'Operator',
            'unit_id' => $this->unitB->id,
            'can_propose' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Administrator SIPAKAR',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'Administrator',
        ]);

        $this->barangAC = MasterBarang::create([
            'kode_barang' => '1.3.2.05.02.04.001',
            'nama_barang' => 'Air Conditioner (AC) Split Wall 2 PK',
            'satuan' => 'Unit',
            'is_active' => true,
        ]);

        $this->barangMeja = MasterBarang::create([
            'kode_barang' => '1.3.2.05.01.01.001',
            'nama_barang' => 'Meja Kerja Dokter Poliklinik',
            'satuan' => 'Buah',
            'is_active' => true,
        ]);
    }

    public function test_operator_can_access_rkbmd_index_and_create_page()
    {
        $indexResp = $this->actingAs($this->pemohon)->get(route('operator.rkbmd.index'));
        $indexResp->assertStatus(200);
        $indexResp->assertSee('Rencana Kebutuhan Barang Milik Daerah (RKBMD)');
        $indexResp->assertSee('Permohonan Saya');

        $createResp = $this->actingAs($this->pemohon)->get(route('operator.rkbmd.create'));
        $createResp->assertStatus(200);
        $createResp->assertSee('Poliklinik Jantung');
        $createResp->assertSee('Air Conditioner');
    }

    public function test_operator_can_submit_rkbmd_with_items_and_pdf_memo()
    {
        $pdf = UploadedFile::fake()->create('memo_intern_poli.pdf', 200, 'application/pdf');

        $response = $this->actingAs($this->pemohon)->post(route('operator.rkbmd.store'), [
            'target_operator_id' => $this->proposerA->id,
            'title' => 'Permohonan Pengadaan AC & Meja Dokter Poli Jantung',
            'year' => 2026,
            'notes' => 'AC lama rusak berat tidak dapat diperbaiki.',
            'attachment' => $pdf,
            'items' => [
                [
                    'master_barang_id' => $this->barangAC->id,
                    'volume' => 1,
                    'satuan' => 'Unit',
                    'spesifikasi' => 'Inverter 2 PK hemat listrik',
                ],
                [
                    'master_barang_id' => $this->barangMeja->id,
                    'volume' => 2,
                    'satuan' => 'Buah',
                    'spesifikasi' => 'Kayu jati dengan 3 laci',
                ],
            ],
        ]);

        $submission = RkbmdSubmission::first();
        $this->assertNotNull($submission);
        $response->assertRedirect(route('operator.rkbmd.show', $submission));

        $this->assertEquals('Diajukan', $submission->status);
        $this->assertEquals($this->pemohon->id, $submission->user_id);
        $this->assertEquals($this->subUnitPoli->id, $submission->sub_unit_id);
        $this->assertEquals($this->proposerA->id, $submission->target_operator_id);
        $this->assertEquals(2, $submission->items()->count());

        // Verifikasi Riwayat Awal Tercatat
        $this->assertDatabaseHas('rkbmd_histories', [
            'rkbmd_submission_id' => $submission->id,
            'action' => 'Pengajuan',
            'to_operator_id' => $this->proposerA->id,
            'status_after' => 'Diajukan',
        ]);
    }

    public function test_audit_columns_and_activity_logs_are_automatically_populated()
    {
        $pdf = UploadedFile::fake()->create('memo_test.pdf', 100, 'application/pdf');

        $this->actingAs($this->pemohon)->post(route('operator.rkbmd.store'), [
            'target_operator_id' => $this->proposerA->id,
            'title' => 'Permohonan Pengadaan Audit Test',
            'year' => 2026,
            'attachment' => $pdf,
            'items' => [
                [
                    'master_barang_id' => $this->barangAC->id,
                    'volume' => 1,
                    'satuan' => 'Unit',
                ],
            ],
        ]);

        $submission = RkbmdSubmission::first();
        $this->assertEquals($this->pemohon->id, $submission->created_by);
        $this->assertEquals($this->pemohon->id, $submission->updated_by);

        // Verifikasi Masuk ke activity_logs (Menu Log Data)
        $this->assertDatabaseHas('activity_logs', [
            'model_type' => RkbmdSubmission::class,
            'model_id' => $submission->id,
            'action' => 'created',
            'user_id' => $this->pemohon->id,
        ]);
    }

    public function test_target_operator_can_reply_with_status_and_free_text()
    {
        $pdf = UploadedFile::fake()->create('memo.pdf', 100, 'application/pdf');

        $this->actingAs($this->pemohon)->post(route('operator.rkbmd.store'), [
            'target_operator_id' => $this->proposerA->id,
            'title' => 'Permohonan Pengadaan AC Poli',
            'year' => 2026,
            'attachment' => $pdf,
            'items' => [
                [
                    'master_barang_id' => $this->barangAC->id,
                    'volume' => 1,
                    'satuan' => 'Unit',
                ],
            ],
        ]);

        $submission = RkbmdSubmission::first();

        // Proposer A membalas (Skenario 1)
        $replyResp = $this->actingAs($this->proposerA)->post(route('operator.rkbmd.reply', $submission), [
            'status' => 'Dipenuhi',
            'reply_notes' => 'Usulan disetujui dan akan dialokasikan pada RBA belanja modal 2026.',
        ]);

        $replyResp->assertRedirect(route('operator.rkbmd.show', $submission));
        $submission->refresh();

        $this->assertEquals('Dipenuhi', $submission->status);
        $this->assertEquals('Usulan disetujui dan akan dialokasikan pada RBA belanja modal 2026.', $submission->reply_notes);
        $this->assertEquals($this->proposerA->id, $submission->replied_by);

        // Verifikasi Histori Balasan
        $this->assertDatabaseHas('rkbmd_histories', [
            'rkbmd_submission_id' => $submission->id,
            'action' => 'Balasan',
            'status_after' => 'Dipenuhi',
        ]);
    }

    public function test_target_operator_can_forward_rkbmd_to_another_proposer_and_logs_history()
    {
        $pdf = UploadedFile::fake()->create('memo.pdf', 100, 'application/pdf');

        $this->actingAs($this->pemohon)->post(route('operator.rkbmd.store'), [
            'target_operator_id' => $this->proposerA->id,
            'title' => 'Permohonan Pengadaan Genset & AC',
            'year' => 2026,
            'attachment' => $pdf,
            'items' => [
                [
                    'master_barang_id' => $this->barangAC->id,
                    'volume' => 2,
                    'satuan' => 'Unit',
                ],
            ],
        ]);

        $submission = RkbmdSubmission::first();

        // Proposer A mengalihkan ke Proposer B (Sarpras)
        $forwardResp = $this->actingAs($this->proposerA)->post(route('operator.rkbmd.forward', $submission), [
            'new_target_operator_id' => $this->proposerB->id,
            'forward_reason' => 'Pengadaan AC dan instalasi pendingin merupakan kewenangan Bagian Sarpras.',
        ]);

        $forwardResp->assertRedirect(route('operator.rkbmd.show', $submission));
        $submission->refresh();

        $this->assertEquals('Dialihkan', $submission->status);
        $this->assertEquals($this->proposerB->id, $submission->target_operator_id);

        // Verifikasi Riwayat Pengalihan Lengkap
        $this->assertDatabaseHas('rkbmd_histories', [
            'rkbmd_submission_id' => $submission->id,
            'action' => 'Pengalihan',
            'from_operator_id' => $this->proposerA->id,
            'to_operator_id' => $this->proposerB->id,
            'notes' => 'Pengadaan AC dan instalasi pendingin merupakan kewenangan Bagian Sarpras.',
        ]);

        // Verifikasi Proposer B kini dapat melihat dan membalas berkas
        $showByNewTarget = $this->actingAs($this->proposerB)->get(route('operator.rkbmd.show', $submission));
        $showByNewTarget->assertStatus(200);
        $showByNewTarget->assertSee('Tindakan Diperlukan');

        // Proposer B memberikan balasan final (Optimalisasi)
        $this->actingAs($this->proposerB)->post(route('operator.rkbmd.reply', $submission), [
            'status' => 'Optimalisasi',
            'reply_notes' => 'Kebutuhan akan dipenuhi melalui relokasi AC eks-ruang rapat lantai 2 yang masih layak pakai.',
        ]);

        $submission->refresh();
        $this->assertEquals('Optimalisasi', $submission->status);
        $this->assertEquals($this->proposerB->id, $submission->replied_by);
    }

    public function test_viewer_operator_cannot_reply_or_forward_rkbmd()
    {
        $pdf = UploadedFile::fake()->create('memo.pdf', 100, 'application/pdf');

        $this->actingAs($this->pemohon)->post(route('operator.rkbmd.store'), [
            'target_operator_id' => $this->proposerA->id,
            'title' => 'Permohonan AC',
            'year' => 2026,
            'attachment' => $pdf,
            'items' => [
                [
                    'master_barang_id' => $this->barangAC->id,
                    'volume' => 1,
                    'satuan' => 'Unit',
                ],
            ],
        ]);

        $submission = RkbmdSubmission::first();

        // Pemohon (bukan target operator) mencoba membalas -> 403 Forbidden
        $replyResp = $this->actingAs($this->pemohon)->post(route('operator.rkbmd.reply', $submission), [
            'status' => 'Dipenuhi',
            'reply_notes' => 'Mencoba membalas sendiri secara ilegal.',
        ]);
        $replyResp->assertStatus(403);

        // Pemohon mencoba mengalihkan -> 403 Forbidden
        $forwardResp = $this->actingAs($this->pemohon)->post(route('operator.rkbmd.forward', $submission), [
            'new_target_operator_id' => $this->proposerB->id,
            'forward_reason' => 'Mencoba mengalihkan tanpa wewenang.',
        ]);
        $forwardResp->assertStatus(403);
    }

    public function test_admin_can_manage_master_barang_permendagri_108()
    {
        $createResp = $this->actingAs($this->admin)->post(route('admin.master-barangs.store'), [
            'kode_barang' => '1.3.2.10.02.99.999',
            'nama_barang' => 'Peralatan Jaringan Switch 24 Port Gigabit',
            'satuan' => 'Unit',
            'deskripsi' => 'Perangkat switch switchboard LAN rumah sakit.',
        ]);

        $createResp->assertRedirect(route('admin.master-barangs.index'));
        $this->assertDatabaseHas('master_barangs', [
            'kode_barang' => '1.3.2.10.02.99.999',
            'nama_barang' => 'Peralatan Jaringan Switch 24 Port Gigabit',
            'created_by' => $this->admin->id,
        ]);

        // Cek log aktivitas
        $this->assertDatabaseHas('activity_logs', [
            'model_type' => MasterBarang::class,
            'action' => 'created',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_admin_master_barangs_index_handles_empty_table_without_breaking_datatables()
    {
        // Pastikan tabel kosong
        MasterBarang::query()->delete();

        $response = $this->actingAs($this->admin)->get(route('admin.master-barangs.index'));
        $response->assertStatus(200);
        $response->assertSee('master-barangs-table');
        $response->assertSee('emptyTable: "Belum ada data master barang BMD."', false);
        // Pastikan tidak ada td colspan di tbody yang merusak inisialisasi DataTables
        $response->assertDontSee('colspan="5"');
    }

    public function test_target_operator_can_edit_reply_and_logs_history()
    {
        $file = UploadedFile::fake()->create('memo_dinas.pdf', 500, 'application/pdf');

        $this->actingAs($this->pemohon)->post(route('operator.rkbmd.store'), [
            'target_operator_id' => $this->proposerA->id,
            'title' => 'Pengadaan Kebutuhan Poli Jantung',
            'year' => 2026,
            'notes' => 'Catatan awal',
            'attachment' => $file,
            'items' => [
                [
                    'master_barang_id' => $this->barangAC->id,
                    'volume' => 1,
                    'satuan' => 'Unit',
                    'spesifikasi' => 'AC 2 PK Inverter',
                ],
            ],
        ]);

        $submission = RkbmdSubmission::first();

        // 1. Berikan balasan awal oleh proposerA
        $this->actingAs($this->proposerA)->post(route('operator.rkbmd.reply', $submission), [
            'status' => 'Dipenuhi',
            'reply_notes' => 'Disetujui penuh untuk masuk usulan RBA tahun depan.',
        ]);

        $submission->refresh();
        $this->assertEquals('Dipenuhi', $submission->status);

        // 2. ProposerA mengedit balasan (misal keliru, mestinya Dipenuhi Sebagian)
        $editResp = $this->actingAs($this->proposerA)->put(route('operator.rkbmd.reply.update', $submission), [
            'status' => 'Dipenuhi Sebagian',
            'reply_notes' => 'Koreksi: Hanya disetujui 1 unit karena alokasi anggaran terbatas.',
        ]);

        $editResp->assertRedirect(route('operator.rkbmd.show', $submission));

        $submission->refresh();
        $this->assertEquals('Dipenuhi Sebagian', $submission->status);
        $this->assertEquals('Koreksi: Hanya disetujui 1 unit karena alokasi anggaran terbatas.', $submission->reply_notes);

        // Cek histori 'Edit Balasan'
        $this->assertDatabaseHas('rkbmd_histories', [
            'rkbmd_submission_id' => $submission->id,
            'user_id' => $this->proposerA->id,
            'action' => 'Edit Balasan',
            'status_before' => 'Dipenuhi',
            'status_after' => 'Dipenuhi Sebagian',
        ]);
    }

    public function test_viewer_and_unauthorized_operator_cannot_edit_reply()
    {
        $file = UploadedFile::fake()->create('memo_dinas.pdf', 500, 'application/pdf');

        $this->actingAs($this->pemohon)->post(route('operator.rkbmd.store'), [
            'target_operator_id' => $this->proposerA->id,
            'title' => 'Pengadaan Kebutuhan Poli Jantung',
            'year' => 2026,
            'attachment' => $file,
            'items' => [
                [
                    'master_barang_id' => $this->barangAC->id,
                    'volume' => 1,
                    'satuan' => 'Unit',
                ],
            ],
        ]);

        $submission = RkbmdSubmission::first();

        // Proposer A balas
        $this->actingAs($this->proposerA)->post(route('operator.rkbmd.reply', $submission), [
            'status' => 'Dipenuhi',
            'reply_notes' => 'Catatan awal.',
        ]);

        // Pemohon (Viewer) mencoba edit balasan -> 403
        $respPemohon = $this->actingAs($this->pemohon)->put(route('operator.rkbmd.reply.update', $submission), [
            'status' => 'Ditolak',
            'reply_notes' => 'Mencoba mengubah secara ilegal.',
        ]);
        $respPemohon->assertStatus(403);

        // Proposer B (operator lain) mencoba edit balasan -> 403
        $respProposerB = $this->actingAs($this->proposerB)->put(route('operator.rkbmd.reply.update', $submission), [
            'status' => 'Ditolak',
            'reply_notes' => 'Bukan pemegang berkas ini.',
        ]);
        $respProposerB->assertStatus(403);
    }

    public function test_admin_can_delegate_master_barangs_menu_permission_to_operator()
    {
        $response = $this->actingAs($this->admin)->put(route('admin.users.update', $this->pemohon), [
            'name' => $this->pemohon->name,
            'email' => $this->pemohon->email,
            'role' => 'Operator',
            'unit_id' => $this->unitA->id,
            'sub_unit_id' => $this->subUnitPoli->id,
            'can_propose' => 0,
            'menu_permissions' => ['master_barangs'],
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->pemohon->refresh();
        $this->assertTrue($this->pemohon->hasMenuPermission('master_barangs'));
        $this->assertContains('master_barangs', $this->pemohon->menu_permissions);
    }

    public function test_operator_with_delegated_permission_can_manage_master_barangs()
    {
        $this->pemohon->update([
            'menu_permissions' => ['master_barangs'],
        ]);

        // 1. Operator bisa membuka index master-barangs dan melihat menu navigasinya
        $responseIndex = $this->actingAs($this->pemohon)->get(route('admin.master-barangs.index'));
        $responseIndex->assertStatus(200);
        $responseIndex->assertSee('Master Barang BMD');

        // 2. Operator bisa membuka form create
        $responseCreate = $this->actingAs($this->pemohon)->get(route('admin.master-barangs.create'));
        $responseCreate->assertStatus(200);

        // 3. Operator bisa menambah master barang baru
        $responseStore = $this->actingAs($this->pemohon)->post(route('admin.master-barangs.store'), [
            'kode_barang' => '1.3.2.05.01.01.009',
            'nama_barang' => 'Sofa Ruang Tunggu Delegasi',
            'satuan' => 'Unit',
            'spesifikasi' => 'Kulit sintetis 3 dudukan',
        ]);

        $responseStore->assertRedirect(route('admin.master-barangs.index'));
        $this->assertDatabaseHas('master_barangs', [
            'kode_barang' => '1.3.2.05.01.01.009',
            'nama_barang' => 'Sofa Ruang Tunggu Delegasi',
        ]);
    }

    public function test_operator_without_delegated_permission_cannot_access_master_barangs()
    {
        // Proposer B tidak memiliki menu_permissions
        $this->assertFalse($this->proposerB->hasMenuPermission('master_barangs'));

        $responseIndex = $this->actingAs($this->proposerB)->get(route('admin.master-barangs.index'));
        $responseIndex->assertStatus(403);

        $responseCreate = $this->actingAs($this->proposerB)->get(route('admin.master-barangs.create'));
        $responseCreate->assertStatus(403);
    }

    public function test_operator_with_delegated_permission_cannot_access_other_admin_menus()
    {
        $this->pemohon->update([
            'menu_permissions' => ['master_barangs'],
        ]);

        // Tetap dilarang membuka menu admin lainnya (RBAC intact)
        $responseUsers = $this->actingAs($this->pemohon)->get(route('admin.users.index'));
        $responseUsers->assertStatus(403);

        $responseUnits = $this->actingAs($this->pemohon)->get(route('admin.units.index'));
        $responseUnits->assertStatus(403);

        $responseDashboard = $this->actingAs($this->pemohon)->get(route('admin.dashboard'));
        $responseDashboard->assertStatus(403);
    }

    public function test_applicant_operator_can_access_edit_page_when_status_diajukan_and_no_forward_or_reply()
    {
        $file = UploadedFile::fake()->create('memo_dinas.pdf', 500, 'application/pdf');

        $this->actingAs($this->pemohon)->post(route('operator.rkbmd.store'), [
            'target_operator_id' => $this->proposerA->id,
            'title' => 'Pengadaan Kebutuhan Poli Jantung Awal',
            'year' => 2026,
            'notes' => 'Catatan awal pengajuan',
            'attachment' => $file,
            'items' => [
                [
                    'master_barang_id' => $this->barangAC->id,
                    'volume' => 2,
                    'satuan' => 'Unit',
                    'spesifikasi' => 'AC 2 PK Inverter',
                ],
            ],
        ]);

        $submission = RkbmdSubmission::first();
        $this->assertTrue($submission->canEditSubmission($this->pemohon));

        $response = $this->actingAs($this->pemohon)->get(route('operator.rkbmd.edit', $submission));
        $response->assertStatus(200);
        $response->assertSee('Edit Permohonan RKBMD');
        $response->assertSee('Pengadaan Kebutuhan Poli Jantung Awal');
    }

    public function test_applicant_operator_can_update_submission_and_items_and_logs_history()
    {
        $file = UploadedFile::fake()->create('memo_dinas.pdf', 500, 'application/pdf');

        $this->actingAs($this->pemohon)->post(route('operator.rkbmd.store'), [
            'target_operator_id' => $this->proposerA->id,
            'title' => 'Pengadaan Kebutuhan Poli Jantung Awal',
            'year' => 2026,
            'notes' => 'Catatan awal pengajuan',
            'attachment' => $file,
            'items' => [
                [
                    'master_barang_id' => $this->barangAC->id,
                    'volume' => 2,
                    'satuan' => 'Unit',
                    'spesifikasi' => 'AC 2 PK Inverter',
                ],
            ],
        ]);

        $submission = RkbmdSubmission::first();

        // Update submission: ganti judul, catatan, dan item menjadi 3 unit meja
        $responseUpdate = $this->actingAs($this->pemohon)->put(route('operator.rkbmd.update', $submission), [
            'target_operator_id' => $this->proposerA->id,
            'title' => 'Revisi: Pengadaan Meja Dokter Poli Jantung',
            'year' => 2026,
            'notes' => 'Catatan revisi setelah koordinasi internal',
            'items' => [
                [
                    'master_barang_id' => $this->barangMeja->id,
                    'volume' => 3,
                    'satuan' => 'Unit',
                    'spesifikasi' => 'Meja Kayu Jati 1/2 Biro',
                ],
            ],
        ]);

        $responseUpdate->assertRedirect(route('operator.rkbmd.show', $submission));

        $submission->refresh();
        $this->assertEquals('Revisi: Pengadaan Meja Dokter Poli Jantung', $submission->title);
        $this->assertEquals('Catatan revisi setelah koordinasi internal', $submission->notes);
        $this->assertEquals('Diajukan', $submission->status);

        // Verifikasi items
        $this->assertCount(1, $submission->items);
        $this->assertEquals($this->barangMeja->id, $submission->items->first()->master_barang_id);
        $this->assertEquals(3, $submission->items->first()->volume);

        // Verifikasi histori 'Edit Permohonan'
        $this->assertDatabaseHas('rkbmd_histories', [
            'rkbmd_submission_id' => $submission->id,
            'user_id' => $this->pemohon->id,
            'action' => 'Edit Permohonan',
            'status_before' => 'Diajukan',
            'status_after' => 'Diajukan',
        ]);
    }

    public function test_applicant_cannot_edit_if_submission_has_been_replied()
    {
        $file = UploadedFile::fake()->create('memo_dinas.pdf', 500, 'application/pdf');

        $this->actingAs($this->pemohon)->post(route('operator.rkbmd.store'), [
            'target_operator_id' => $this->proposerA->id,
            'title' => 'Pengadaan Kebutuhan Poli Jantung',
            'year' => 2026,
            'attachment' => $file,
            'items' => [
                [
                    'master_barang_id' => $this->barangAC->id,
                    'volume' => 1,
                    'satuan' => 'Unit',
                ],
            ],
        ]);

        $submission = RkbmdSubmission::first();

        // Target operator membalas permohonan
        $this->actingAs($this->proposerA)->post(route('operator.rkbmd.reply', $submission), [
            'status' => 'Dipenuhi',
            'reply_notes' => 'Disetujui penuh.',
        ]);

        $submission->refresh();
        $this->assertFalse($submission->canEditSubmission($this->pemohon));

        // Pemohon mencoba akses halaman edit -> 403
        $respEdit = $this->actingAs($this->pemohon)->get(route('operator.rkbmd.edit', $submission));
        $respEdit->assertStatus(403);

        // Pemohon mencoba request update -> 403
        $respUpdate = $this->actingAs($this->pemohon)->put(route('operator.rkbmd.update', $submission), [
            'target_operator_id' => $this->proposerA->id,
            'title' => 'Coba ubah setelah dibalas',
            'year' => 2026,
            'items' => [
                [
                    'master_barang_id' => $this->barangAC->id,
                    'volume' => 5,
                    'satuan' => 'Unit',
                ],
            ],
        ]);
        $respUpdate->assertStatus(403);
    }

    public function test_applicant_cannot_edit_if_submission_has_been_forwarded()
    {
        $file = UploadedFile::fake()->create('memo_dinas.pdf', 500, 'application/pdf');

        $this->actingAs($this->pemohon)->post(route('operator.rkbmd.store'), [
            'target_operator_id' => $this->proposerA->id,
            'title' => 'Pengadaan Kebutuhan Poli Jantung',
            'year' => 2026,
            'attachment' => $file,
            'items' => [
                [
                    'master_barang_id' => $this->barangAC->id,
                    'volume' => 1,
                    'satuan' => 'Unit',
                ],
            ],
        ]);

        $submission = RkbmdSubmission::first();

        // Target operator mengalihkan berkas ke Proposer B
        $this->actingAs($this->proposerA)->post(route('operator.rkbmd.forward', $submission), [
            'new_target_operator_id' => $this->proposerB->id,
            'forward_reason' => 'Bukan wewenang poli kami, dialihkan ke Sarpras.',
        ]);

        $submission->refresh();
        $this->assertFalse($submission->canEditSubmission($this->pemohon));

        // Pemohon mencoba akses halaman edit -> 403
        $respEdit = $this->actingAs($this->pemohon)->get(route('operator.rkbmd.edit', $submission));
        $respEdit->assertStatus(403);

        // Pemohon mencoba request update -> 403
        $respUpdate = $this->actingAs($this->pemohon)->put(route('operator.rkbmd.update', $submission), [
            'target_operator_id' => $this->proposerB->id,
            'title' => 'Coba ubah setelah dialihkan',
            'year' => 2026,
            'items' => [
                [
                    'master_barang_id' => $this->barangAC->id,
                    'volume' => 5,
                    'satuan' => 'Unit',
                ],
            ],
        ]);
        $respUpdate->assertStatus(403);
    }

    public function test_unauthorized_operator_cannot_edit_submission()
    {
        $file = UploadedFile::fake()->create('memo_dinas.pdf', 500, 'application/pdf');

        $this->actingAs($this->pemohon)->post(route('operator.rkbmd.store'), [
            'target_operator_id' => $this->proposerA->id,
            'title' => 'Pengadaan Kebutuhan Poli Jantung',
            'year' => 2026,
            'attachment' => $file,
            'items' => [
                [
                    'master_barang_id' => $this->barangAC->id,
                    'volume' => 1,
                    'satuan' => 'Unit',
                ],
            ],
        ]);

        $submission = RkbmdSubmission::first();

        // Operator lain (proposerB) mencoba mengedit permohonan milik pemohon -> 403
        $respEdit = $this->actingAs($this->proposerB)->get(route('operator.rkbmd.edit', $submission));
        $respEdit->assertStatus(403);

        $respUpdate = $this->actingAs($this->proposerB)->put(route('operator.rkbmd.update', $submission), [
            'target_operator_id' => $this->proposerA->id,
            'title' => 'Perubahan ilegal oleh pihak ketiga',
            'year' => 2026,
            'items' => [
                [
                    'master_barang_id' => $this->barangAC->id,
                    'volume' => 1,
                    'satuan' => 'Unit',
                ],
            ],
        ]);
        $respUpdate->assertStatus(403);
    }

    public function test_forwarding_operator_sees_forwarded_submission_in_dedicated_tab()
    {
        $file = UploadedFile::fake()->create('memo_dinas.pdf', 500, 'application/pdf');

        $this->actingAs($this->pemohon)->post(route('operator.rkbmd.store'), [
            'target_operator_id' => $this->proposerA->id,
            'title' => 'Pengadaan Kebutuhan Poli Jantung',
            'year' => 2026,
            'attachment' => $file,
            'items' => [
                [
                    'master_barang_id' => $this->barangAC->id,
                    'volume' => 1,
                    'satuan' => 'Unit',
                ],
            ],
        ]);

        $submission = RkbmdSubmission::first();

        // 1. Proposer A mengalihkan ke Proposer B
        $this->actingAs($this->proposerA)->post(route('operator.rkbmd.forward', $submission), [
            'new_target_operator_id' => $this->proposerB->id,
            'forward_reason' => 'Bukan tupoksi kami, dialihkan ke Sarpras.',
        ]);

        // 2. Cek index dari sisi Proposer A (Pengalih)
        $respA = $this->actingAs($this->proposerA)->get(route('operator.rkbmd.index'));
        $respA->assertStatus(200);
        $respA->assertSee('Permohonan Dialihkan');
        $respA->assertSee('Daftar Permohonan yang Anda Alihkan');
        $respA->assertSee($submission->nomor_permohonan);
        $respA->assertSee($this->proposerB->name);

        $forwardedA = $respA->viewData('forwardedSubmissions');
        $this->assertTrue($forwardedA->contains('id', $submission->id));

        $incomingA = $respA->viewData('incomingSubmissions');
        $this->assertFalse($incomingA->contains('id', $submission->id));

        // 3. Cek index dari sisi Proposer B (Penerima alihan)
        $respB = $this->actingAs($this->proposerB)->get(route('operator.rkbmd.index'));
        $respB->assertStatus(200);
        $incomingB = $respB->viewData('incomingSubmissions');
        $this->assertTrue($incomingB->contains('id', $submission->id));
    }

    public function test_forwarding_operator_can_view_show_page_and_banner_of_forwarded_submission()
    {
        $file = UploadedFile::fake()->create('memo_dinas.pdf', 500, 'application/pdf');

        $this->actingAs($this->pemohon)->post(route('operator.rkbmd.store'), [
            'target_operator_id' => $this->proposerA->id,
            'title' => 'Pengadaan Kebutuhan Poli Jantung',
            'year' => 2026,
            'attachment' => $file,
            'items' => [
                [
                    'master_barang_id' => $this->barangAC->id,
                    'volume' => 1,
                    'satuan' => 'Unit',
                ],
            ],
        ]);

        $submission = RkbmdSubmission::first();

        // Proposer A mengalihkan ke Proposer B
        $this->actingAs($this->proposerA)->post(route('operator.rkbmd.forward', $submission), [
            'new_target_operator_id' => $this->proposerB->id,
            'forward_reason' => 'Bukan tupoksi kami, dialihkan ke Sarpras.',
        ]);

        // Proposer A melihat halaman show
        $response = $this->actingAs($this->proposerA)->get(route('operator.rkbmd.show', $submission));
        $response->assertStatus(200);
        $response->assertSee('Berkas Telah Anda Alihkan');
        $response->assertSee($this->proposerB->name);
        $response->assertSee('Bukan tupoksi kami, dialihkan ke Sarpras.');
    }

    public function test_forwarded_submission_status_updates_when_resolved_by_target_operator()
    {
        $file = UploadedFile::fake()->create('memo_dinas.pdf', 500, 'application/pdf');

        $this->actingAs($this->pemohon)->post(route('operator.rkbmd.store'), [
            'target_operator_id' => $this->proposerA->id,
            'title' => 'Pengadaan Kebutuhan Poli Jantung',
            'year' => 2026,
            'attachment' => $file,
            'items' => [
                [
                    'master_barang_id' => $this->barangAC->id,
                    'volume' => 1,
                    'satuan' => 'Unit',
                ],
            ],
        ]);

        $submission = RkbmdSubmission::first();

        // Proposer A mengalihkan ke Proposer B
        $this->actingAs($this->proposerA)->post(route('operator.rkbmd.forward', $submission), [
            'new_target_operator_id' => $this->proposerB->id,
            'forward_reason' => 'Bukan tupoksi kami, dialihkan ke Sarpras.',
        ]);

        // Proposer B memberikan balasan Dipenuhi
        $this->actingAs($this->proposerB)->post(route('operator.rkbmd.reply', $submission), [
            'status' => 'Dipenuhi',
            'reply_notes' => 'Telah disetujui Sarpras.',
        ]);

        // Proposer A melihat index -> status di tab forwarded terupdate menjadi Dipenuhi
        $respA = $this->actingAs($this->proposerA)->get(route('operator.rkbmd.index'));
        $respA->assertStatus(200);
        $forwardedA = $respA->viewData('forwardedSubmissions');
        $this->assertEquals('Dipenuhi', $forwardedA->firstWhere('id', $submission->id)->status);
    }
}



