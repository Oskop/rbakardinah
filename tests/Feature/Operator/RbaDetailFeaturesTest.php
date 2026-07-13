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

class RbaDetailFeaturesTest extends TestCase
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
            'email' => 'operator' . uniqid() . '@test.com',
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

    public function test_operator_can_create_rba_detail()
    {
        $file = UploadedFile::fake()->create('detail.pdf', 100);

        $response = $this->actingAs($this->operator)->post(route('operator.details.store'), [
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Test create',
            'volume' => 1,
            'satuan' => 'Pcs',
            'harga_satuan' => 5000,
            'attachment' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('rba_details', [
            'description' => 'Test create',
            'volume' => 1.00,
            'satuan' => 'Pcs',
            'harga_satuan' => 5000.00,
            'nominal_request' => 5000.00
        ]);
    }

    public function test_operator_can_submit_item()
    {
        $detail = RbaDetail::create([
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'To Submit',
            'volume' => 1,
            'satuan' => 'Pkt',
            'harga_satuan' => 1000,
            'nominal_request' => 1000,
            'created_by' => $this->operator->id,
            'is_submitted' => false
        ]);

        $response = $this->actingAs($this->operator)->post(route('operator.details.submit-item', $detail));
        $response->assertStatus(302);

        $this->assertTrue($detail->fresh()->is_submitted);
        $this->assertEquals('Pending Supervisor', $this->submission->fresh()->status_submission);
    }

    public function test_operator_can_soft_delete_item()
    {
        $detail = RbaDetail::create([
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'To Delete',
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
}
