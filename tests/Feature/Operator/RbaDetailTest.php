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
            'nominal_request' => 5000000,
            'attachment' => $file,
        ]);

        $response->assertRedirect(route('operator.submissions.show', $this->submission->id));
        $this->assertDatabaseHas('rba_details', ['description' => 'Pembelian Alat Tulis']);
        $this->assertDatabaseHas('rba_attachments', ['version_number' => 1]);

        $detail = RbaDetail::where('description', 'Pembelian Alat Tulis')->first();
        $this->assertNotNull($detail);
        $filePath = $detail->attachments->first()->file_path;
        Storage::disk('public')->assertExists($filePath);
    }

    public function test_operator_can_upload_new_version_of_pdf()
    {
        $detail = RbaDetail::create([
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Test Item',
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
        // Refresh detail to get updated attachments
        $this->assertEquals(2, $detail->fresh()->attachments()->count());
        $this->assertDatabaseHas('rba_attachments', ['version_number' => 2]);
    }

    public function test_operator_can_submit_item_to_supervisor()
    {
        $detail = RbaDetail::create([
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Test Item',
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
            'nominal_request' => 1000,
            'created_by' => $this->operator->id
        ]);

        $response = $this->actingAs($this->operator)->delete(route('operator.details.destroy', $detail));
        $response->assertStatus(302);

        $this->assertTrue($detail->fresh()->trashed());
    }

    public function test_operator_must_upload_new_pdf_when_nominal_exceeds_pagu()
    {
        // 1. Set pagu global
        $pagu = \App\Models\RbaAccountPagu::create([
            'rba_header_id' => $this->submission->rba_header_id,
            'account_code_id' => $this->accountCode->id,
            'nominal_pagu' => 500000,
        ]);

        // 2. Create detail that exceeds pagu
        $detail = RbaDetail::create([
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Exceeding item',
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

        // 3. Attempt to submit should fail
        $response = $this->actingAs($this->operator)->post(route('operator.details.submit-item', $detail));
        $response->assertSessionHas('error');
        $this->assertFalse($detail->fresh()->is_submitted);

        // 4. Upload revision PDF (created_at >= pagu->updated_at)
        $fileV2 = UploadedFile::fake()->create('v2.pdf', 100);
        $this->actingAs($this->operator)->post(route('operator.details.upload-version', $detail), [
            'attachment' => $fileV2,
        ]);

        // 5. Attempt to submit should now succeed
        $response = $this->actingAs($this->operator)->post(route('operator.details.submit-item', $detail));
        $response->assertSessionHas('success');
        $this->assertTrue($detail->fresh()->is_submitted);
    }

    public function test_supervisor_cannot_validate_item_exceeding_pagu_without_revision()
    {
        // 1. Set pagu global
        $pagu = \App\Models\RbaAccountPagu::create([
            'rba_header_id' => $this->submission->rba_header_id,
            'account_code_id' => $this->accountCode->id,
            'nominal_pagu' => 500000,
        ]);

        // 2. Create detail that exceeds pagu
        $detail = RbaDetail::create([
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Exceeding item',
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

        $supervisor = User::create([
            'name' => 'Supervisor Test',
            'email' => 'supervisor@test.com',
            'password' => bcrypt('password'),
            'role' => 'Supervisor',
            'unit_id' => $this->unit->id,
        ]);

        // 3. Attempt to validate should fail
        $response = $this->actingAs($supervisor)->post(route('supervisor.details.toggle-validation', $detail));
        $response->assertSessionHas('error');
        $this->assertFalse($detail->fresh()->is_validated);

        // 4. Operator uploads revision
        $fileV2 = UploadedFile::fake()->create('v2.pdf', 100);
        $this->actingAs($this->operator)->post(route('operator.details.upload-version', $detail), [
            'attachment' => $fileV2,
        ]);

        // 5. Supervisor attempts to validate should now succeed
        $response = $this->actingAs($supervisor)->post(route('supervisor.details.toggle-validation', $detail));
        $response->assertSessionHas('success');
        $this->assertTrue($detail->fresh()->is_validated);
    }

    public function test_operator_cannot_add_detail_if_background_is_empty()
    {
        // Set background to null
        $this->submission->update(['background' => null]);

        $file = UploadedFile::fake()->create('detail.pdf', 100);

        $response = $this->actingAs($this->operator)->post(route('operator.details.store'), [
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Test Item',
            'nominal_request' => 5000000,
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
        // 1. Lock header
        $this->submission->header->update(['status_global' => 'Locked']);

        // 2. Upload KAK V1
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

        // 3. Upload KAK V2
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
}
