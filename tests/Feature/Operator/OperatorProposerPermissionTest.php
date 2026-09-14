<?php

namespace Tests\Feature\Operator;

use App\Models\User;
use App\Models\Unit;
use App\Models\SubUnit;
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

class OperatorProposerPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected Unit $unit;
    protected SubUnit $subUnit;
    protected User $proposer;
    protected User $viewer;
    protected RbaHeader $header;
    protected RbaSubmission $submission;
    protected AccountCode $accountCode;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->unit = Unit::create(['code' => 'U01', 'name' => 'Bagian Farmasi']);
        $this->subUnit = SubUnit::create([
            'unit_id' => $this->unit->id,
            'name' => 'Gudang Farmasi',
            'code' => 'GF01',
            'type' => 'Pelayanan',
            'is_active' => true,
        ]);

        $this->proposer = User::create([
            'name' => 'Operator Pengusul',
            'email' => 'pengusul@test.com',
            'password' => bcrypt('password'),
            'role' => 'Operator',
            'unit_id' => $this->unit->id,
            'sub_unit_id' => $this->subUnit->id,
            'can_propose' => true,
        ]);

        $this->viewer = User::create([
            'name' => 'Operator Viewer',
            'email' => 'viewer@test.com',
            'password' => bcrypt('password'),
            'role' => 'Operator',
            'unit_id' => $this->unit->id,
            'sub_unit_id' => $this->subUnit->id,
            'can_propose' => false,
        ]);

        $period = RbaPeriod::create(['name' => 'Perencanaan Murni']);
        $admin = User::create([
            'name' => 'Admin RBA',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'Administrator',
        ]);

        $this->header = RbaHeader::create([
            'period_id' => $period->id,
            'year' => 2026,
            'admin_id' => $admin->id,
            'status_global' => 'Draft',
        ]);

        $this->submission = RbaSubmission::create([
            'rba_header_id' => $this->header->id,
            'unit_id' => $this->unit->id,
            'status_submission' => 'Draft',
            'background' => 'Latar Belakang Dasar Unit Farmasi',
        ]);

        $group = KelompokBelanja::create(['kode' => 'KB01', 'name' => 'Operasional']);
        $this->accountCode = AccountCode::create([
            'kelompok_belanja_id' => $group->id,
            'code' => '5.1.02.01',
            'name' => 'Belanja Bahan Obat-obatan',
            'is_active' => true,
        ]);
    }

    public function test_proposer_operator_can_access_create_detail_page()
    {
        $response = $this->actingAs($this->proposer)
            ->get(route('operator.details.create', ['submission_id' => $this->submission->id]));

        $response->assertStatus(200);
        $response->assertSee('Tambah Rincian Belanja');
    }

    public function test_viewer_operator_cannot_access_create_detail_page_and_gets_403()
    {
        $response = $this->actingAs($this->viewer)
            ->get(route('operator.details.create', ['submission_id' => $this->submission->id]));

        $response->assertStatus(403);
    }

    public function test_proposer_operator_can_store_detail()
    {
        $pdfFile = UploadedFile::fake()->create('dokumen_kak.pdf', 150, 'application/pdf');

        $response = $this->actingAs($this->proposer)
            ->post(route('operator.details.store'), [
                'rba_submission_id' => $this->submission->id,
                'account_code_id' => $this->accountCode->id,
                'description' => 'Pengadaan Paracetamol Infus 1000 Botol',
                'volume' => 100,
                'satuan' => 'Botol',
                'harga_satuan' => 25000,
                'attachment' => $pdfFile,
            ]);

        $response->assertRedirect(route('operator.submissions.show', $this->submission->id));
        $this->assertDatabaseHas('rba_details', [
            'rba_submission_id' => $this->submission->id,
            'description' => 'Pengadaan Paracetamol Infus 1000 Botol',
            'created_by' => $this->proposer->id,
        ]);
    }

    public function test_viewer_operator_cannot_store_detail_and_gets_403()
    {
        $pdfFile = UploadedFile::fake()->create('dokumen_kak.pdf', 150, 'application/pdf');

        $response = $this->actingAs($this->viewer)
            ->post(route('operator.details.store'), [
                'rba_submission_id' => $this->submission->id,
                'account_code_id' => $this->accountCode->id,
                'description' => 'Pengadaan Ilegal oleh Viewer',
                'volume' => 10,
                'satuan' => 'Unit',
                'harga_satuan' => 50000,
                'attachment' => $pdfFile,
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('rba_details', [
            'description' => 'Pengadaan Ilegal oleh Viewer',
        ]);
    }

    public function test_proposer_operator_can_update_background()
    {
        $response = $this->actingAs($this->proposer)
            ->put(route('operator.submissions.update-background', $this->submission), [
                'background' => 'Kebutuhan obat meningkat 20 persen tahun ini.',
            ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('rba_submission_operator_backgrounds', [
            'rba_submission_id' => $this->submission->id,
            'user_id' => $this->proposer->id,
            'background' => 'Kebutuhan obat meningkat 20 persen tahun ini.',
        ]);
    }

    public function test_viewer_operator_cannot_update_background_and_gets_403()
    {
        $response = $this->actingAs($this->viewer)
            ->put(route('operator.submissions.update-background', $this->submission), [
                'background' => 'Percobaan ubah background oleh viewer.',
            ]);

        $response->assertStatus(403);
    }

    public function test_proposer_operator_can_submit_submission_to_supervisor()
    {
        $response = $this->actingAs($this->proposer)
            ->post(route('operator.submissions.submit', $this->submission));

        $response->assertRedirect(route('operator.submissions.index'));
        $this->assertEquals('Pending Supervisor', $this->submission->fresh()->status_submission);
    }

    public function test_viewer_operator_cannot_submit_submission_and_gets_403()
    {
        $response = $this->actingAs($this->viewer)
            ->post(route('operator.submissions.submit', $this->submission));

        $response->assertStatus(403);
        $this->assertEquals('Draft', $this->submission->fresh()->status_submission);
    }

    public function test_viewer_operator_cannot_upload_document_and_gets_403()
    {
        // Set header locked
        $this->header->update(['status_global' => 'Locked']);

        $pdfFile = UploadedFile::fake()->create('dokumen_kak.pdf', 150, 'application/pdf');

        $response = $this->actingAs($this->viewer)
            ->post(route('operator.submissions.documents.upload', $this->submission), [
                'type' => 'KAK',
                'attachment' => $pdfFile,
            ]);

        $response->assertStatus(403);
    }

    public function test_viewer_operator_can_view_submission_and_print_preview()
    {
        // Create detail created by proposer
        RbaDetail::create([
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Antibiotik Injeksi',
            'volume' => 50,
            'satuan' => 'Vial',
            'harga_satuan' => 30000,
            'nominal_request' => 1500000,
            'created_by' => $this->proposer->id,
        ]);

        // Viewer access show page
        $showResponse = $this->actingAs($this->viewer)
            ->get(route('operator.submissions.show', $this->submission));

        $showResponse->assertStatus(200);
        $showResponse->assertSee('Mode Peninjau (Hanya Lihat)');
        $showResponse->assertSee('Antibiotik Injeksi');

        // Viewer access print preview
        $printResponse = $this->actingAs($this->viewer)
            ->get(route('operator.submissions.print-preview', $this->submission));

        $printResponse->assertStatus(200);
        $printResponse->assertSee('Antibiotik Injeksi');
    }

    public function test_supervisor_can_create_and_update_operator_with_can_propose_flag()
    {
        $supervisor = User::create([
            'name' => 'Supervisor Farmasi',
            'email' => 'spv@test.com',
            'password' => bcrypt('password'),
            'role' => 'Supervisor',
            'unit_id' => $this->unit->id,
        ]);

        // Supervisor creates viewer operator
        $createResponse = $this->actingAs($supervisor)
            ->post(route('supervisor.users.store'), [
                'name' => 'Staf Gudang Baru',
                'email' => 'gudangbaru@test.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'sub_unit_id' => $this->subUnit->id,
                'can_propose' => '0',
            ]);

        $createResponse->assertRedirect(route('supervisor.users.index'));
        $createdUser = User::where('email', 'gudangbaru@test.com')->first();
        $this->assertNotNull($createdUser);
        $this->assertFalse($createdUser->can_propose);
        $this->assertFalse($createdUser->isProposer());

        // Supervisor updates to proposer
        $updateResponse = $this->actingAs($supervisor)
            ->patch(route('supervisor.users.update', $createdUser), [
                'name' => 'Staf Gudang Baru Dipromosikan',
                'email' => 'gudangbaru@test.com',
                'is_active' => 1,
                'sub_unit_id' => $this->subUnit->id,
                'can_propose' => '1',
            ]);

        $updateResponse->assertRedirect(route('supervisor.users.index'));
        $this->assertTrue($createdUser->fresh()->can_propose);
        $this->assertTrue($createdUser->fresh()->isProposer());
    }
}
