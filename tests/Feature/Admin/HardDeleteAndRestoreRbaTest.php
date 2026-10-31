<?php

namespace Tests\Feature\Admin;

use App\Models\AccountCode;
use App\Models\ActivityLog;
use App\Models\HardDeleteArchive;
use App\Models\KelompokBelanja;
use App\Models\RbaAccountPagu;
use App\Models\RbaDetail;
use App\Models\RbaHeader;
use App\Models\RbaPeriod;
use App\Models\RbaSubmission;
use App\Models\Unit;
use App\Models\User;
use App\Services\HardDeleteArchiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HardDeleteAndRestoreRbaTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $operator;
    protected Unit $unit;
    protected RbaPeriod $period;
    protected AccountCode $accountCode;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'Administrator',
            'is_active' => true,
        ]);

        $this->unit = Unit::create(['name' => 'Unit Pelayanan Medis', 'code' => 'UPM']);

        $this->operator = User::factory()->create([
            'role' => 'Operator',
            'is_active' => true,
            'unit_id' => $this->unit->id,
            'can_propose' => true,
        ]);

        $this->period = RbaPeriod::create([
            'name' => 'RBA Murni 2026',
            'is_active' => true,
        ]);

        $kelompok = KelompokBelanja::create(['name' => 'Belanja Operasional', 'kode' => '5.1', 'is_active' => true]);
        $this->accountCode = AccountCode::create([
            'kelompok_belanja_id' => $kelompok->id,
            'code' => '5.1.02.01.01',
            'name' => 'Belanja Bahan Medis Habis Pakai',
            'is_active' => true,
        ]);
    }

    public function test_hard_delete_archives_entire_tree_and_cleans_operational_tables(): void
    {
        // 1. Setup RbaHeader with submissions, pagu, and details
        $header = RbaHeader::create([
            'period_id' => $this->period->id,
            'admin_id' => $this->admin->id,
            'year' => 2026,
            'status_global' => 'Draft',
        ]);
        $headerId = $header->id;

        $submission = RbaSubmission::create([
            'rba_header_id' => $headerId,
            'unit_id' => $this->unit->id,
            'status_submission' => 'Draft',
            'background' => 'Latar belakang unit permohonan',
        ]);
        $submissionId = $submission->id;

        $pagu = RbaAccountPagu::create([
            'rba_header_id' => $headerId,
            'account_code_id' => $this->accountCode->id,
            'nominal_pagu' => 50000000,
        ]);

        $detail = RbaDetail::create([
            'rba_submission_id' => $submissionId,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Spuit 5cc dan Jarum Suntik',
            'volume' => 100,
            'satuan' => 'Box',
            'harga_satuan' => 50000,
            'nominal_request' => 5000000,
            'created_by' => $this->operator->id,
        ]);
        $detailId = $detail->id;

        // 2. Perform Hard Delete via Service
        $archiveService = app(HardDeleteArchiveService::class);
        $archive = $archiveService->deleteRbaHeader($header, 'Salah input tahun anggaran', $this->admin->id);

        // 3. Assert Archive is recorded with complete snapshot
        $this->assertDatabaseHas('hard_delete_archives', [
            'id' => $archive->id,
            'model_type' => RbaHeader::class,
            'model_id' => $headerId,
            'reason' => 'Salah input tahun anggaran',
            'deleted_by' => $this->admin->id,
        ]);

        $this->assertNull($archive->restored_at);
        $this->assertEquals($headerId, $archive->root_data['id']);
        $this->assertCount(1, $archive->related_data['rba_submissions']);
        $this->assertCount(1, $archive->related_data['rba_account_pagus']);
        $this->assertCount(1, $archive->related_data['rba_details']);

        // 4. Assert Operational Tables are completely cleaned
        $this->assertDatabaseMissing('rba_headers', ['id' => $headerId]);
        $this->assertDatabaseMissing('rba_submissions', ['id' => $submissionId]);
        $this->assertDatabaseMissing('rba_account_pagus', ['rba_header_id' => $headerId]);
        $this->assertDatabaseMissing('rba_details', ['id' => $detailId]);

        // 5. Assert ActivityLog is logged for BPK audit trail
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => 'deleted',
            'model_type' => RbaHeader::class,
            'model_id' => $headerId,
        ]);
    }

    public function test_restore_reconstructs_exact_ids_and_all_relations_100_percent(): void
    {
        // 1. Create and delete an RBA Header
        $header = RbaHeader::create([
            'period_id' => $this->period->id,
            'admin_id' => $this->admin->id,
            'year' => 2026,
            'status_global' => 'Draft',
        ]);
        $headerId = $header->id;

        $submission = RbaSubmission::create([
            'rba_header_id' => $headerId,
            'unit_id' => $this->unit->id,
            'status_submission' => 'Draft',
        ]);
        $submissionId = $submission->id;

        $detail = RbaDetail::create([
            'rba_submission_id' => $submissionId,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Item penting yang sempat terhapus',
            'volume' => 10,
            'satuan' => 'Pcs',
            'harga_satuan' => 100000,
            'nominal_request' => 1000000,
            'created_by' => $this->operator->id,
        ]);
        $detailId = $detail->id;

        $archiveService = app(HardDeleteArchiveService::class);
        $archive = $archiveService->deleteRbaHeader($header, 'Pengujian Pemulihan Data', $this->admin->id);

        $this->assertDatabaseMissing('rba_headers', ['id' => $headerId]);

        // 2. Perform Restore
        $restoredHeader = $archiveService->restoreRbaHeader($archive, $this->admin->id);

        // 3. Assert header is restored with EXACT same ID and attributes
        $this->assertEquals($headerId, $restoredHeader->id);
        $this->assertEquals(2026, $restoredHeader->year);
        $this->assertEquals($this->period->id, $restoredHeader->period_id);
        $this->assertDatabaseHas('rba_headers', ['id' => $headerId]);

        // 4. Assert children are restored with EXACT same IDs and relation integrity
        $this->assertDatabaseHas('rba_submissions', [
            'id' => $submissionId,
            'rba_header_id' => $headerId,
            'unit_id' => $this->unit->id,
        ]);

        $this->assertDatabaseHas('rba_details', [
            'id' => $detailId,
            'rba_submission_id' => $submissionId,
            'description' => 'Item penting yang sempat terhapus',
            'nominal_request' => 1000000,
        ]);

        // 5. Assert archive record is marked as restored
        $archive->refresh();
        $this->assertTrue($archive->isRestored());
        $this->assertNotNull($archive->restored_at);
        $this->assertEquals($this->admin->id, $archive->restored_by);

        // 6. Assert ActivityLog for restore is recorded
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => 'restored',
            'model_type' => RbaHeader::class,
            'model_id' => $headerId,
        ]);
    }

    public function test_restore_fails_gracefully_if_collision_occurs(): void
    {
        // 1. Create and delete an RBA Header
        $header = RbaHeader::create([
            'period_id' => $this->period->id,
            'admin_id' => $this->admin->id,
            'year' => 2026,
            'status_global' => 'Draft',
        ]);
        $headerId = $header->id;

        $archiveService = app(HardDeleteArchiveService::class);
        $archive = $archiveService->deleteRbaHeader($header, 'Test collision', $this->admin->id);

        // 2. Now simulate admin creating a NEW RBA Header with the SAME period and year
        RbaHeader::create([
            'period_id' => $this->period->id,
            'admin_id' => $this->admin->id,
            'year' => 2026,
            'status_global' => 'Draft',
        ]);

        // 3. Attempting to restore the old one must throw an exception because period+year is unique
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('sudah ada di tabel operasional');

        $archiveService->restoreRbaHeader($archive, $this->admin->id);
    }

    public function test_admin_can_delete_header_and_restore_via_web_routes(): void
    {
        $header = RbaHeader::create([
            'period_id' => $this->period->id,
            'admin_id' => $this->admin->id,
            'year' => 2026,
            'status_global' => 'Draft',
        ]);
        $headerId = $header->id;

        // 1. Delete via HTTP DELETE /admin/headers/{header}
        $responseDelete = $this->actingAs($this->admin)->delete(route('admin.headers.destroy', $header), [
            'reason' => 'Salah nama periode oleh staf admin',
        ]);

        $responseDelete->assertRedirect(route('admin.headers.index'));
        $responseDelete->assertSessionHas('success');

        $this->assertDatabaseMissing('rba_headers', ['id' => $headerId]);
        $archive = HardDeleteArchive::where('model_id', $headerId)->first();
        $this->assertNotNull($archive);

        // 2. View Archives index & show
        $responseIndex = $this->actingAs($this->admin)->get(route('admin.archives.index'));
        $responseIndex->assertOk();
        $responseIndex->assertSee('Salah nama periode oleh staf admin');
        $responseIndex->assertSee('Arsip Data Terhapus');

        $responseShow = $this->actingAs($this->admin)->get(route('admin.archives.show', $archive));
        $responseShow->assertOk();
        $responseShow->assertSee('Detail Snapshot Arsip');

        // 3. Restore via HTTP POST /admin/archives/{archive}/restore
        $responseRestore = $this->actingAs($this->admin)->post(route('admin.archives.restore', $archive));
        $responseRestore->assertRedirect(route('admin.headers.show', $headerId));
        $responseRestore->assertSessionHas('success');

        $this->assertDatabaseHas('rba_headers', ['id' => $headerId]);
        $archive->refresh();
        $this->assertTrue($archive->isRestored());
    }

    public function test_artisan_commands_can_delete_and_restore_header(): void
    {
        $header = RbaHeader::create([
            'period_id' => $this->period->id,
            'admin_id' => $this->admin->id,
            'year' => 2026,
            'status_global' => 'Draft',
        ]);
        $headerId = $header->id;

        // Run artisan rba:delete-header with --force
        $this->artisan("rba:delete-header {$headerId} --reason='Test via artisan' --force")
            ->assertExitCode(0);

        $this->assertDatabaseMissing('rba_headers', ['id' => $headerId]);
        $archive = HardDeleteArchive::where('model_id', $headerId)->first();
        $this->assertNotNull($archive);

        // Run artisan rba:restore-header with --force
        $this->artisan("rba:restore-header {$archive->id} --force")
            ->assertExitCode(0);

        $this->assertDatabaseHas('rba_headers', ['id' => $headerId]);
        $archive->refresh();
        $this->assertTrue($archive->isRestored());
    }
}
