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

    public function test_operator_can_create_rba_detail_with_long_description()
    {
        $file = UploadedFile::fake()->create('detail.pdf', 100);
        $longDescription = 'Storage Server dengan spek 2x Intel Xeon Scalable (Gold Gen 5), minimal 32 Cores, 256 GB / 512 GB ECC Registered DDR5 (Dapat diekspansi hingga 1 TB+), 2x SSD 480GB Enterprise SATA/NVMe (RAID 1), Enterprise NVMe U.2 / U.3 (Model Read/Write Intensive) minimal 10 TB, Dual-port 10 Gbps SFP+ PCIe Network Adapter, Redundant Power Supply (1+1) Hot-Swap, 80+ Platinum/Titanium (min. 800W - 1200W)';

        $response = $this->actingAs($this->operator)->post(route('operator.details.store'), [
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => $longDescription,
            'volume' => 1,
            'satuan' => 'Unit',
            'harga_satuan' => 350000000,
            'attachment' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('rba_details', [
            'description' => $longDescription,
            'nominal_request' => 350000000.00
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

    public function test_operator_can_preview_print_report_with_and_without_background()
    {
        // Test preview with background
        $responseWithBg = $this->actingAs($this->operator)->get(route('operator.submissions.print-preview', [
            'submission' => $this->submission->id,
            'include_background' => 1
        ]));
        $responseWithBg->assertStatus(200);
        $responseWithBg->assertSee('LATAR BELAKANG SUB-UNIT');
        $responseWithBg->assertSee('Latar belakang unit testing');

        // Test preview without background
        $responseNoBg = $this->actingAs($this->operator)->get(route('operator.submissions.print-preview', [
            'submission' => $this->submission->id,
            'include_background' => 0
        ]));
        $responseNoBg->assertStatus(200);
        $responseNoBg->assertDontSee('LATAR BELAKANG SUB-UNIT');
    }

    public function test_operator_can_export_pdf_report()
    {
        $response = $this->actingAs($this->operator)->get(route('operator.submissions.export-pdf', [
            'submission' => $this->submission->id,
            'include_background' => 1
        ]));
        $response->assertStatus(200);
    }

    public function test_operator_can_preview_rba_final_print_report()
    {
        \App\Models\RbaAccountPagu::create([
            'rba_header_id' => $this->submission->rba_header_id,
            'account_code_id' => $this->accountCode->id,
            'nominal_pagu' => 50000000
        ]);

        RbaDetail::create([
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Item Testing RBA Final',
            'volume' => 1,
            'satuan' => 'Pkt',
            'harga_satuan' => 45000000,
            'nominal_request' => 45000000,
            'created_by' => $this->operator->id
        ]);

        $response = $this->actingAs($this->operator)->get(route('operator.submissions.print-preview-final', [
            'submission' => $this->submission->id,
            'include_background' => 1
        ]));

        $response->assertStatus(200);
        $response->assertSee('USULAN RINCIAN RENCANA BELANJA DAN ANGGARAN DAN PAGU FINAL (RBA FINAL)');
        $response->assertSee('PAGU FINAL (Rp)');
        $response->assertSee('Rp 50.000.000');
    }
}
