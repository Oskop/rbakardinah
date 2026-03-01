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
            'admin_id' => 1, // Assume admin exists or just set ID
            'status_global' => 'Draft'
        ]);

        $this->submission = RbaSubmission::create([
            'rba_header_id' => $header->id,
            'unit_id' => $this->unit->id,
            'status_submission' => 'Draft'
        ]);

        $group = \App\Models\KelompokBelanja::create(['name' => 'Test Group']);
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

        $detail = RbaDetail::first();
        Storage::disk('public')->assertExists($detail->attachments->first()->file_path);
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
        $this->assertEquals(2, $detail->attachments()->count());
        $this->assertDatabaseHas('rba_attachments', ['version_number' => 2]);
    }

    public function test_operator_can_submit_to_supervisor()
    {
        $response = $this->actingAs($this->operator)->post(route('operator.submissions.submit', $this->submission));
        $response->assertRedirect(route('operator.submissions.index'));
        $this->assertEquals('Pending Supervisor', $this->submission->fresh()->status_submission);
    }
}
