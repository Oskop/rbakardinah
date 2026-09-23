<?php

namespace Tests\Feature\Operator;

use App\Models\User;
use App\Models\Unit;
use App\Models\RbaHeader;
use App\Models\RbaPeriod;
use App\Models\RbaSubmission;
use App\Models\AccountCode;
use App\Models\KelompokBelanja;
use App\Models\RbaDetail;
use App\Models\RbaAccountPagu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RbaDetailTest extends TestCase
{
    use RefreshDatabase;

    protected $operator;
    protected $unit;
    protected $submission;
    protected $accountCode;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->unit = Unit::create(['code' => 'U01', 'name' => 'Unit Testing']);
        $this->operator = User::create([
            'name' => 'Operator Test',
            'email' => 'operator@test.com',
            'password' => bcrypt('password'),
            'role' => 'Operator',
            'unit_id' => $this->unit->id,
        ]);

        $period = RbaPeriod::create(['name' => 'Murni']);
        $header = RbaHeader::create([
            'period_id' => $period->id,
            'year' => 2026,
            'admin_id' => 1,
            'status_global' => 'Draft'
        ]);

        $this->submission = RbaSubmission::create([
            'rba_header_id' => $header->id,
            'unit_id' => $this->unit->id,
            'status_submission' => 'Draft',
            'background' => 'Latar belakang unit testing'
        ]);

        $group = KelompokBelanja::create(['kode' => 'KB01', 'name' => 'Test Group']);
        $this->accountCode = AccountCode::create([
            'kelompok_belanja_id' => $group->id,
            'code' => '5.1.01',
            'name' => 'Belanja ATK'
        ]);
    }

    public function test_operator_can_view_their_submissions()
    {
        $response = $this->actingAs($this->operator)->get(route('operator.submissions.index'));
        $response->assertStatus(200);
        $response->assertSee('2026');
    }

    public function test_operator_can_create_rba_detail_with_pdf()
    {
        $file = UploadedFile::fake()->create('detail.pdf', 100);

        $response = $this->actingAs($this->operator)->post(route('operator.details.store'), [
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Pembelian Alat Tulis',
            'volume' => 100,
            'satuan' => 'Pcs',
            'harga_satuan' => 50000,
            'attachment' => $file,
        ]);

        $response->assertRedirect(route('operator.submissions.show', $this->submission->id));
        $this->assertDatabaseHas('rba_details', [
            'description' => 'Pembelian Alat Tulis',
            'volume' => 100.00,
            'satuan' => 'Pcs',
            'harga_satuan' => 50000.00,
            'nominal_request' => 5000000.00
        ]);
        $this->assertDatabaseHas('rba_attachments', ['version_number' => 1]);

        $detail = RbaDetail::where('description', 'Pembelian Alat Tulis')->first();
        $this->assertNotNull($detail);
        $filePath = $detail->attachments->first()->file_path;
        Storage::disk('public')->assertExists($filePath);
    }

    public function test_operator_submission_view_displays_previous_period_pagu_in_awal_column()
    {
        // 1. Setup 2025 Perubahan Header with Pagu
        $period2025 = RbaPeriod::create(['name' => 'Perubahan']);
        $header2025 = RbaHeader::create([
            'period_id' => $period2025->id,
            'year' => 2025,
            'admin_id' => 1,
            'status_global' => 'Locked'
        ]);

        RbaAccountPagu::create([
            'rba_header_id' => $header2025->id,
            'account_code_id' => $this->accountCode->id,
            'nominal_pagu' => 15000000
        ]);

        // 2. Create detail in current 2026 Murni submission
        RbaDetail::create([
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Usulan 2026 ATK',
            'volume' => 10,
            'satuan' => 'Paket',
            'harga_satuan' => 1000000,
            'nominal_request' => 10000000,
            'created_by' => $this->operator->id
        ]);

        // 3. Access submission show page
        $response = $this->actingAs($this->operator)->get(route('operator.submissions.show', $this->submission->id));

        $response->assertStatus(200);
        $response->assertSee('AWAL');
        $response->assertSee('Rp 15.000.000');
    }

    public function test_operator_can_upload_new_version_of_pdf()
    {
        $detail = RbaDetail::create([
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Test Item',
            'volume' => 1,
            'satuan' => 'Pkt',
            'harga_satuan' => 1000,
            'nominal_request' => 1000,
            'created_by' => $this->operator->id
        ]);

        $fileV1 = UploadedFile::fake()->create('v1.pdf', 100);
        $detail->attachments()->create([
            'file_path' => $fileV1->store('attachments', 'public'),
            'version_number' => 1,
            'uploaded_by' => $this->operator->id
        ]);

        $fileV2 = UploadedFile::fake()->create('v2.pdf', 100);
        $response = $this->actingAs($this->operator)->post(route('operator.details.upload-version', $detail), [
            'attachment' => $fileV2,
        ]);

        $response->assertStatus(302);
        $this->assertEquals(2, $detail->fresh()->attachments()->count());
        $this->assertDatabaseHas('rba_attachments', ['version_number' => 2]);
    }

    public function test_operator_can_submit_item_to_supervisor()
    {
        $detail = RbaDetail::create([
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Test Item',
            'volume' => 1,
            'satuan' => 'Pkt',
            'harga_satuan' => 1000,
            'nominal_request' => 1000,
            'created_by' => $this->operator->id
        ]);

        $response = $this->actingAs($this->operator)->post(route('operator.details.submit-item', $detail));
        $response->assertStatus(302);

        $detail->refresh();
        $this->assertTrue($detail->is_submitted);
        $this->assertEquals('Pending Supervisor', $this->submission->fresh()->status_submission);
    }

    public function test_operator_can_soft_delete_rba_detail()
    {
        $detail = RbaDetail::create([
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'To be deleted',
            'volume' => 1,
            'satuan' => 'Pkt',
            'harga_satuan' => 1000,
            'nominal_request' => 1000,
            'created_by' => $this->operator->id
        ]);

        $response = $this->actingAs($this->operator)->delete(route('operator.details.destroy', $detail));
        $response->assertStatus(302);

        $this->assertTrue($detail->fresh()->trashed());
    }

    public function test_operator_must_upload_new_pdf_when_nominal_exceeds_pagu()
    {
        $pagu = RbaAccountPagu::create([
            'rba_header_id' => $this->submission->rba_header_id,
            'account_code_id' => $this->accountCode->id,
            'nominal_pagu' => 500000,
        ]);

        $detail = RbaDetail::create([
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Exceeding item',
            'volume' => 1,
            'satuan' => 'Pkt',
            'harga_satuan' => 600000,
            'nominal_request' => 600000,
            'created_by' => $this->operator->id,
        ]);

        $fileV1 = UploadedFile::fake()->create('v1.pdf', 100);
        $att1 = $detail->attachments()->create([
            'file_path' => $fileV1->store('attachments', 'public'),
            'version_number' => 1,
            'uploaded_by' => $this->operator->id,
        ]);
        $att1->timestamps = false;
        $att1->created_at = now()->subMinutes(5);
        $att1->save();

        $response = $this->actingAs($this->operator)->post(route('operator.details.submit-item', $detail));
        $response->assertSessionHas('error');
        $this->assertFalse($detail->fresh()->is_submitted);

        $fileV2 = UploadedFile::fake()->create('v2.pdf', 100);
        $this->actingAs($this->operator)->post(route('operator.details.upload-version', $detail), [
            'attachment' => $fileV2,
        ]);

        $response = $this->actingAs($this->operator)->post(route('operator.details.submit-item', $detail));
        $response->assertSessionHas('success');
        $this->assertTrue($detail->fresh()->is_submitted);
    }

    public function test_supervisor_cannot_validate_item_exceeding_pagu_without_revision()
    {
        $pagu = RbaAccountPagu::create([
            'rba_header_id' => $this->submission->rba_header_id,
            'account_code_id' => $this->accountCode->id,
            'nominal_pagu' => 500000,
        ]);

        $detail = RbaDetail::create([
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Exceeding item',
            'volume' => 1,
            'satuan' => 'Pkt',
            'harga_satuan' => 600000,
            'nominal_request' => 600000,
            'is_submitted' => true,
            'created_by' => $this->operator->id,
        ]);

        $fileV1 = UploadedFile::fake()->create('v1.pdf', 100);
        $att1 = $detail->attachments()->create([
            'file_path' => $fileV1->store('attachments', 'public'),
            'version_number' => 1,
            'uploaded_by' => $this->operator->id,
        ]);
        $att1->timestamps = false;
        $att1->created_at = now()->subMinutes(5);
        $att1->save();

        $supervisor = User::create([
            'name' => 'Supervisor Test',
            'email' => 'supervisor@test.com',
            'password' => bcrypt('password'),
            'role' => 'Supervisor',
            'unit_id' => $this->unit->id,
        ]);

        $response = $this->actingAs($supervisor)->post(route('supervisor.details.toggle-validation', $detail));
        $response->assertSessionHas('error');
        $this->assertFalse($detail->fresh()->is_validated);

        $fileV2 = UploadedFile::fake()->create('v2.pdf', 100);
        $this->actingAs($this->operator)->post(route('operator.details.upload-version', $detail), [
            'attachment' => $fileV2,
        ]);

        $this->actingAs($this->operator)->post(route('operator.details.submit-item', $detail));

        $response = $this->actingAs($supervisor)->post(route('supervisor.details.toggle-validation', $detail));
        $response->assertSessionHas('success');
        $this->assertTrue($detail->fresh()->is_validated);
    }

    public function test_operator_cannot_add_detail_if_background_is_empty()
    {
        $this->submission->update(['background' => null]);

        $file = UploadedFile::fake()->create('detail.pdf', 100);

        $response = $this->actingAs($this->operator)->post(route('operator.details.store'), [
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Test Item',
            'volume' => 100,
            'satuan' => 'Pcs',
            'harga_satuan' => 50000,
            'attachment' => $file,
        ]);

        $response->assertRedirect(route('operator.submissions.show', $this->submission->id));
        $response->assertSessionHas('error', 'Sebelum menginput rincian belanja, Anda wajib mengisi data latar belakang terlebih dahulu.');
        $this->assertDatabaseMissing('rba_details', ['description' => 'Test Item']);
    }

    public function test_operator_can_save_background()
    {
        $this->submission->update(['background' => null]);

        $response = $this->actingAs($this->operator)->put(route('operator.submissions.update-background', $this->submission), [
            'background' => 'Ini adalah teks latar belakang baru yang diisi oleh operator.'
        ]);

        $response->assertSessionHas('success', 'Latar belakang RBA berhasil diperbarui.');
        $this->assertEquals('Ini adalah teks latar belakang baru yang diisi oleh operator.', $this->submission->fresh()->background);
    }

    public function test_operator_can_upload_kak_rak_rtp_versioned_documents_when_locked()
    {
        $this->submission->header->update(['status_global' => 'Locked']);

        $fileV1 = UploadedFile::fake()->create('kak_v1.pdf', 100);
        $response = $this->actingAs($this->operator)->post(route('operator.submissions.documents.upload', $this->submission), [
            'type' => 'KAK',
            'attachment' => $fileV1,
        ]);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('rba_submission_documents', [
            'rba_submission_id' => $this->submission->id,
            'type' => 'KAK'
        ]);

        $doc = \App\Models\RbaSubmissionDocument::where('rba_submission_id', $this->submission->id)->where('type', 'KAK')->first();
        $this->assertNotNull($doc);
        $this->assertDatabaseHas('rba_submission_document_versions', [
            'rba_submission_document_id' => $doc->id,
            'version_number' => 1
        ]);

        $fileV2 = UploadedFile::fake()->create('kak_v2.pdf', 100);
        $response = $this->actingAs($this->operator)->post(route('operator.submissions.documents.upload', $this->submission), [
            'type' => 'KAK',
            'attachment' => $fileV2,
        ]);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('rba_submission_document_versions', [
            'rba_submission_document_id' => $doc->id,
            'version_number' => 2
        ]);
    }

    public function test_operator_cannot_edit_or_upload_revision_on_validated_detail()
    {
        $supervisor = User::factory()->create(['role' => 'Supervisor', 'unit_id' => $this->operator->unit_id]);

        $detail = RbaDetail::create([
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Original Validated Description',
            'volume' => 5,
            'satuan' => 'Pcs',
            'harga_satuan' => 100000,
            'nominal_request' => 500000,
            'is_submitted' => true,
            'is_validated' => true,
            'validated_at' => now(),
            'validated_by' => $supervisor->id,
            'created_by' => $this->operator->id
        ]);

        // 1. Edit view should return 403 Forbidden
        $responseEdit = $this->actingAs($this->operator)->get(route('operator.details.edit', $detail));
        $responseEdit->assertStatus(403);

        // 2. Update request should return 403 Forbidden
        $responseUpdate = $this->actingAs($this->operator)->put(route('operator.details.update', $detail), [
            'account_code_id' => $this->accountCode->id,
            'description' => 'Updated Description Attempt',
            'volume' => 10,
            'satuan' => 'Pcs',
            'harga_satuan' => 100000,
        ]);
        $responseUpdate->assertStatus(403);

        // 3. Upload revision PDF on validated detail should return 403 Forbidden
        $file = UploadedFile::fake()->create('revisi.pdf', 100);
        $responseUpload = $this->actingAs($this->operator)->post(route('operator.details.upload-version', $detail), [
            'attachment' => $file,
        ]);
        $responseUpload->assertStatus(403);

        // 4. Delete on validated detail should return 403 Forbidden
        $responseDelete = $this->actingAs($this->operator)->delete(route('operator.details.destroy', $detail));
        $responseDelete->assertStatus(403);

        $freshDetail = $detail->fresh();
        $this->assertEquals('Original Validated Description', $freshDetail->description);
        $this->assertTrue($freshDetail->is_validated);
    }

    public function test_uploading_revision_pdf_on_rejected_detail_resets_status_to_draft()
    {
        $supervisor = User::factory()->create(['role' => 'Supervisor', 'unit_id' => $this->operator->unit_id]);

        // 1. Rejected detail
        $detail = RbaDetail::create([
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Rejected Proposal Item',
            'volume' => 2,
            'satuan' => 'Unit',
            'harga_satuan' => 100000,
            'nominal_request' => 200000,
            'is_submitted' => false,
            'is_validated' => false,
            'is_rejected' => true,
            'rejected_at' => now(),
            'rejected_by' => $supervisor->id,
            'rejection_reason' => 'Perbaiki lampiran spesifikasi teknis',
            'created_by' => $this->operator->id
        ]);

        $fileV1 = UploadedFile::fake()->create('v1.pdf', 100);
        $detail->attachments()->create([
            'file_path' => $fileV1->store('attachments', 'public'),
            'version_number' => 1,
            'uploaded_by' => $this->operator->id,
        ]);

        // 2. Operator uploads revised PDF (version 2)
        $fileV2 = UploadedFile::fake()->create('v2_revisi.pdf', 100);
        $response = $this->actingAs($this->operator)->post(route('operator.details.upload-version', $detail), [
            'attachment' => $fileV2,
        ]);

        $response->assertSessionHas('success');

        $fresh = $detail->fresh();
        $this->assertFalse($fresh->is_rejected);
        $this->assertNull($fresh->rejected_at);
        $this->assertNull($fresh->rejected_by);
        $this->assertNull($fresh->rejection_reason);
        $this->assertFalse($fresh->is_validated);
        $this->assertFalse($fresh->is_submitted);
        $this->assertEquals(2, $fresh->attachments()->count());

        // 3. Operator can submit the item
        $resSubmit = $this->actingAs($this->operator)->post(route('operator.details.submit-item', $detail));
        $resSubmit->assertSessionHas('success');
        $this->assertTrue($detail->fresh()->is_submitted);
    }

    public function test_operator_can_create_detail_with_named_document()
    {
        $file = UploadedFile::fake()->create('nota_dinas_atk.pdf', 100);

        $response = $this->actingAs($this->operator)->post(route('operator.details.store'), [
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Kertas HVS A4 80gr',
            'volume' => 50,
            'satuan' => 'Rim',
            'harga_satuan' => 60000,
            'document_source' => 'new',
            'document_name' => 'Nota Dinas Pengadaan ATK Q1',
            'attachment' => $file,
        ]);

        $response->assertRedirect(route('operator.submissions.show', $this->submission->id));
        $this->assertDatabaseHas('rba_detail_documents', [
            'rba_submission_id' => $this->submission->id,
            'document_name' => 'Nota Dinas Pengadaan ATK Q1',
        ]);

        $detail = RbaDetail::where('description', 'Kertas HVS A4 80gr')->first();
        $this->assertNotNull($detail);
        $this->assertEquals('Nota Dinas Pengadaan ATK Q1', $detail->document()?->document_name);
        $this->assertEquals(1, $detail->latestAttachment()->version_number);
    }

    public function test_operator_can_create_subsequent_detail_using_existing_shared_pdf()
    {
        // 1. Usulan pertama membuat dokumen PDF baru
        $file = UploadedFile::fake()->create('nota_dinas_atk.pdf', 100);
        $this->actingAs($this->operator)->post(route('operator.details.store'), [
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Item 1: Kertas HVS',
            'volume' => 10,
            'satuan' => 'Rim',
            'harga_satuan' => 50000,
            'document_source' => 'new',
            'document_name' => 'Nota Dinas ATK 2026',
            'attachment' => $file,
        ]);

        $item1 = RbaDetail::where('description', 'Item 1: Kertas HVS')->first();
        $doc = $item1->document();
        $this->assertNotNull($doc);

        // 2. Usulan kedua memilih dokumen yang sudah ada (tanpa upload file baru)
        $response = $this->actingAs($this->operator)->post(route('operator.details.store'), [
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Item 2: Tinta Printer',
            'volume' => 5,
            'satuan' => 'Botol',
            'harga_satuan' => 120000,
            'document_source' => 'existing',
            'rba_detail_document_id' => $doc->id,
        ]);

        $response->assertRedirect(route('operator.submissions.show', $this->submission->id));

        $item2 = RbaDetail::where('description', 'Item 2: Tinta Printer')->first();
        $this->assertNotNull($item2);

        // Keduanya berbagi file attachment yang sama persis
        $this->assertEquals($item1->latestAttachment()->id, $item2->latestAttachment()->id);
        $this->assertEquals(2, $item1->latestAttachment()->details()->count());
    }

    public function test_shared_pdf_revising_updates_selected_items_and_leaves_unselected()
    {
        // 1. Buat dokumen dengan 3 usulan
        $file = UploadedFile::fake()->create('nota_dinas_atk.pdf', 100);
        $this->actingAs($this->operator)->post(route('operator.details.store'), [
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Usulan 1',
            'volume' => 1,
            'satuan' => 'Pcs',
            'harga_satuan' => 10000,
            'document_name' => 'Nota Dinas ATK Bersama',
            'attachment' => $file,
        ]);
        $item1 = RbaDetail::where('description', 'Usulan 1')->first();
        $doc = $item1->document();

        // Usulan 2 dan 3 gunakan dokumen yang sama
        $this->actingAs($this->operator)->post(route('operator.details.store'), [
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Usulan 2',
            'volume' => 1,
            'satuan' => 'Pcs',
            'harga_satuan' => 20000,
            'document_source' => 'existing',
            'rba_detail_document_id' => $doc->id,
        ]);
        $item2 = RbaDetail::where('description', 'Usulan 2')->first();

        $this->actingAs($this->operator)->post(route('operator.details.store'), [
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Usulan 3 (Ditolak)',
            'volume' => 1,
            'satuan' => 'Pcs',
            'harga_satuan' => 30000,
            'document_source' => 'existing',
            'rba_detail_document_id' => $doc->id,
        ]);
        $item3 = RbaDetail::where('description', 'Usulan 3 (Ditolak)')->first();

        // 2. Operator unggah versi revisi V2 untuk Usulan 1 dan Usulan 2 saja (Usulan 3 dikecualikan)
        $fileV2 = UploadedFile::fake()->create('nota_dinas_atk_rev.pdf', 100);
        $res = $this->actingAs($this->operator)->post(route('operator.details.upload-version', $item1), [
            'attachment' => $fileV2,
            'upload_mode' => 'shared_update',
            'target_detail_ids' => [$item1->id, $item2->id], // hanya item 1 dan 2
        ]);
        $res->assertSessionHas('success');

        $fresh1 = $item1->fresh();
        $fresh2 = $item2->fresh();
        $fresh3 = $item3->fresh();

        // Usulan 1 dan 2 sekarang memiliki versi 2
        $this->assertEquals(2, $fresh1->latestAttachment()->version_number);
        $this->assertEquals(2, $fresh2->latestAttachment()->version_number);
        $this->assertEquals($fresh1->latestAttachment()->id, $fresh2->latestAttachment()->id);

        // Usulan 3 tetap berada di versi 1
        $this->assertEquals(1, $fresh3->latestAttachment()->version_number);
    }

    public function test_detaching_item_to_new_document_creates_standalone_history()
    {
        // 1. Buat 2 usulan berbagi dokumen
        $file = UploadedFile::fake()->create('dokumen_gabung.pdf', 100);
        $this->actingAs($this->operator)->post(route('operator.details.store'), [
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Item Tetap',
            'volume' => 1,
            'satuan' => 'Pcs',
            'harga_satuan' => 10000,
            'document_name' => 'Dokumen Gabung',
            'attachment' => $file,
        ]);
        $item1 = RbaDetail::where('description', 'Item Tetap')->first();
        $doc = $item1->document();

        $this->actingAs($this->operator)->post(route('operator.details.store'), [
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Item Pisah',
            'volume' => 1,
            'satuan' => 'Pcs',
            'harga_satuan' => 20000,
            'document_source' => 'existing',
            'rba_detail_document_id' => $doc->id,
        ]);
        $item2 = RbaDetail::where('description', 'Item Pisah')->first();

        // 2. Item 2 memilih mode standalone (Pisahkan Dokumen)
        $fileNew = UploadedFile::fake()->create('dokumen_mandiri_baru.pdf', 100);
        $res = $this->actingAs($this->operator)->post(route('operator.details.upload-version', $item2), [
            'attachment' => $fileNew,
            'upload_mode' => 'standalone',
            'document_name' => 'Dokumen Mandiri Item 2',
        ]);
        $res->assertSessionHas('success');

        $fresh1 = $item1->fresh();
        $fresh2 = $item2->fresh();

        // Dokumen Item 1 tidak berubah
        $this->assertEquals('Dokumen Gabung', $fresh1->document()->document_name);

        // Dokumen Item 2 sekarang adalah dokumen mandiri baru
        $this->assertEquals('Dokumen Mandiri Item 2', $fresh2->document()->document_name);
        $this->assertNotEquals($fresh1->latestAttachment()->id, $fresh2->latestAttachment()->id);

        // Riwayat Item 2 memiliki 2 versi lampiran (versi 1 lama dan versi 2 baru)
        $this->assertEquals(2, $fresh2->attachments()->count());
    }

    public function test_deleting_one_item_preserves_shared_attachment_for_other_items()
    {
        $file = UploadedFile::fake()->create('dokumen_share.pdf', 100);
        $this->actingAs($this->operator)->post(route('operator.details.store'), [
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Item A',
            'volume' => 1,
            'satuan' => 'Pcs',
            'harga_satuan' => 10000,
            'attachment' => $file,
        ]);
        $itemA = RbaDetail::where('description', 'Item A')->first();

        $this->actingAs($this->operator)->post(route('operator.details.store'), [
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Item B',
            'volume' => 1,
            'satuan' => 'Pcs',
            'harga_satuan' => 20000,
            'document_source' => 'existing',
            'rba_detail_document_id' => $itemA->document()->id,
        ]);
        $itemB = RbaDetail::where('description', 'Item B')->first();
        $attachmentId = $itemB->latestAttachment()->id;

        // Hapus Item A
        $this->actingAs($this->operator)->delete(route('operator.details.destroy', $itemA));

        // Attachment dan Item B tetap aman
        $this->assertSoftDeleted('rba_details', ['id' => $itemA->id]);
        $this->assertDatabaseHas('rba_attachments', ['id' => $attachmentId]);
        $this->assertEquals($attachmentId, $itemB->fresh()->latestAttachment()->id);
    }

    public function test_operator_can_edit_detail_and_upload_new_pdf_document()
    {
        $file1 = UploadedFile::fake()->create('dokumen_awal.pdf', 100);
        $this->actingAs($this->operator)->post(route('operator.details.store'), [
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Item Awal',
            'volume' => 5,
            'satuan' => 'Pcs',
            'harga_satuan' => 10000,
            'document_name' => 'Dokumen Lama Awal',
            'attachment' => $file1,
        ]);

        $detail = RbaDetail::where('description', 'Item Awal')->first();
        $this->assertEquals('Dokumen Lama Awal', $detail->document()->document_name);

        // Edit dan unggah berkas PDF baru
        $fileNew = UploadedFile::fake()->create('dokumen_baru_edit.pdf', 150);
        $response = $this->actingAs($this->operator)->put(route('operator.details.update', $detail), [
            'account_code_id' => $this->accountCode->id,
            'description' => 'Item Awal Diperbarui',
            'volume' => 8,
            'satuan' => 'Pcs',
            'harga_satuan' => 12000,
            'document_action' => 'new',
            'document_name' => 'Dokumen Baru Pasca Edit',
            'attachment' => $fileNew,
        ]);

        $response->assertRedirect(route('operator.submissions.show', $this->submission->id));
        $response->assertSessionHas('success');

        $freshDetail = $detail->fresh();
        $this->assertEquals('Item Awal Diperbarui', $freshDetail->description);
        $this->assertEquals(8, $freshDetail->volume);
        $this->assertEquals('Dokumen Baru Pasca Edit', $freshDetail->document()->document_name);
        $this->assertEquals('dokumen_baru_edit.pdf', $freshDetail->latestAttachment()->original_filename);
        $this->assertFalse($freshDetail->is_submitted);
        $this->assertFalse($freshDetail->is_validated);
    }

    public function test_operator_can_edit_detail_and_switch_to_existing_document()
    {
        // 1. Dokumen 1
        $file1 = UploadedFile::fake()->create('doc1.pdf', 100);
        $this->actingAs($this->operator)->post(route('operator.details.store'), [
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Item 1',
            'volume' => 1,
            'satuan' => 'Pcs',
            'harga_satuan' => 10000,
            'document_name' => 'Dokumen 1',
            'attachment' => $file1,
        ]);
        $item1 = RbaDetail::where('description', 'Item 1')->first();

        // 2. Dokumen 2
        $file2 = UploadedFile::fake()->create('doc2.pdf', 100);
        $this->actingAs($this->operator)->post(route('operator.details.store'), [
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Item 2',
            'volume' => 1,
            'satuan' => 'Pcs',
            'harga_satuan' => 20000,
            'document_name' => 'Dokumen 2 Target',
            'attachment' => $file2,
        ]);
        $item2 = RbaDetail::where('description', 'Item 2')->first();
        $doc2 = $item2->document();

        // 3. Edit Item 1 beralih ke Dokumen 2
        $response = $this->actingAs($this->operator)->put(route('operator.details.update', $item1), [
            'account_code_id' => $this->accountCode->id,
            'description' => 'Item 1 Switch Doc',
            'volume' => 2,
            'satuan' => 'Pcs',
            'harga_satuan' => 10000,
            'document_action' => 'existing',
            'rba_detail_document_id' => $doc2->id,
        ]);

        $response->assertRedirect(route('operator.submissions.show', $this->submission->id));
        $freshItem1 = $item1->fresh();
        $this->assertEquals($doc2->id, $freshItem1->document()->id);
        $this->assertEquals('Dokumen 2 Target', $freshItem1->document()->document_name);
    }

    public function test_operator_can_edit_detail_keeping_current_document()
    {
        $file = UploadedFile::fake()->create('doc_tetap.pdf', 100);
        $this->actingAs($this->operator)->post(route('operator.details.store'), [
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Item Tetap Doc',
            'volume' => 3,
            'satuan' => 'Pcs',
            'harga_satuan' => 15000,
            'document_name' => 'Dokumen Tetap',
            'attachment' => $file,
        ]);
        $detail = RbaDetail::where('description', 'Item Tetap Doc')->first();
        $initialAttId = $detail->latestAttachment()->id;

        // Edit tanpa mengubah dokumen (document_action = keep)
        $response = $this->actingAs($this->operator)->put(route('operator.details.update', $detail), [
            'account_code_id' => $this->accountCode->id,
            'description' => 'Item Tetap Doc Diperbarui',
            'volume' => 5,
            'satuan' => 'Pcs',
            'harga_satuan' => 15000,
            'document_action' => 'keep',
        ]);

        $response->assertRedirect(route('operator.submissions.show', $this->submission->id));
        $freshDetail = $detail->fresh();
        $this->assertEquals('Item Tetap Doc Diperbarui', $freshDetail->description);
        $this->assertEquals(5, $freshDetail->volume);
        $this->assertEquals($initialAttId, $freshDetail->latestAttachment()->id);
        $this->assertEquals('Dokumen Tetap', $freshDetail->document()->document_name);
    }

    public function test_operator_can_switch_detail_to_existing_document_via_upload_version()
    {
        // 1. Buat usulan 1 dengan Dokumen A
        $fileA = UploadedFile::fake()->create('doc_a.pdf', 100);
        $this->actingAs($this->operator)->post(route('operator.details.store'), [
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Item Dokumen A',
            'volume' => 1,
            'satuan' => 'Pcs',
            'harga_satuan' => 10000,
            'document_name' => 'Dokumen Sumber A',
            'attachment' => $fileA,
        ]);
        $item1 = RbaDetail::where('description', 'Item Dokumen A')->first();
        $docA = $item1->document();

        // 2. Buat usulan 2 dengan Dokumen B
        $fileB = UploadedFile::fake()->create('doc_b.pdf', 100);
        $this->actingAs($this->operator)->post(route('operator.details.store'), [
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Item Dokumen B',
            'volume' => 2,
            'satuan' => 'Pcs',
            'harga_satuan' => 25000,
            'document_name' => 'Dokumen Target B',
            'attachment' => $fileB,
        ]);
        $item2 = RbaDetail::where('description', 'Item Dokumen B')->first();
        $docB = $item2->document();

        // 3. Alihkan Item 1 ke Dokumen B via route operator.details.upload-version
        $response = $this->actingAs($this->operator)->post(route('operator.details.upload-version', $item1), [
            'source_mode' => 'existing',
            'rba_detail_document_id' => $docB->id,
        ]);

        $response->assertSessionHas('success');
        $fresh1 = $item1->fresh();
        $this->assertEquals($docB->id, $fresh1->document()->id);
        $this->assertEquals('Dokumen Target B', $fresh1->document()->document_name);
        $this->assertEquals($docB->latestVersion->id, $fresh1->latestAttachment()->id);
        $this->assertFalse($fresh1->is_validated);
        $this->assertFalse($fresh1->is_submitted);
    }

    public function test_switching_to_existing_document_resets_rejected_status_to_draft()
    {
        $file = UploadedFile::fake()->create('doc_awal.pdf', 100);
        $this->actingAs($this->operator)->post(route('operator.details.store'), [
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Item Ditolak',
            'volume' => 1,
            'satuan' => 'Pcs',
            'harga_satuan' => 10000,
            'document_name' => 'Dokumen Awal',
            'attachment' => $file,
        ]);
        $item = RbaDetail::where('description', 'Item Ditolak')->first();

        // Buat dokumen lain yang valid
        $file2 = UploadedFile::fake()->create('doc_pengganti.pdf', 100);
        $this->actingAs($this->operator)->post(route('operator.details.store'), [
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Item Lain',
            'volume' => 2,
            'satuan' => 'Pcs',
            'harga_satuan' => 20000,
            'document_name' => 'Dokumen Pengganti',
            'attachment' => $file2,
        ]);
        $itemOther = RbaDetail::where('description', 'Item Lain')->first();
        $docReplacement = $itemOther->document();

        // Tandai item sebagai ditolak
        $item->update([
            'is_submitted' => true,
            'is_rejected' => true,
            'rejection_reason' => 'Perbaiki berkas pendukung',
        ]);

        // Alihkan item ditolak ke dokumen pengganti
        $res = $this->actingAs($this->operator)->post(route('operator.details.upload-version', $item), [
            'source_mode' => 'existing',
            'rba_detail_document_id' => $docReplacement->id,
        ]);

        $res->assertSessionHas('success');
        $freshItem = $item->fresh();
        $this->assertFalse($freshItem->is_rejected);
        $this->assertNull($freshItem->rejection_reason);
        $this->assertFalse($freshItem->is_submitted);
        $this->assertEquals($docReplacement->id, $freshItem->document()->id);
    }
}
