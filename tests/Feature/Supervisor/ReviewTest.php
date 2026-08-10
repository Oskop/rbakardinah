<?php

namespace Tests\Feature\Supervisor;

use App\Models\User;
use App\Models\Unit;
use App\Models\RbaPeriod;
use App\Models\RbaHeader;
use App\Models\RbaSubmission;
use App\Models\AccountCode;
use App\Models\KelompokBelanja;
use App\Models\RbaDetail;
use App\Models\RbaAccountPagu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected $supervisor;
    protected $submission;
    protected $accountCode;

    protected function setUp(): void
    {
        parent::setUp();

        $unit = Unit::create(['code' => 'U01', 'name' => 'Unit Test']);
        $group = KelompokBelanja::create(['kode' => 'KB01', 'name' => 'Test Group']);
        $this->accountCode = AccountCode::create([
            'kelompok_belanja_id' => $group->id,
            'code' => '5.1.01',
            'name' => 'Belanja Gaji'
        ]);
        $this->supervisor = User::factory()->create(['role' => 'Supervisor', 'unit_id' => $unit->id]);

        $admin = User::factory()->create(['role' => 'Administrator']);
        $period = RbaPeriod::create(['name' => 'Murni']);
        $header = RbaHeader::create([
            'period_id' => $period->id,
            'year' => 2026,
            'admin_id' => $admin->id,
            'status_global' => 'Active'
        ]);

        $this->submission = RbaSubmission::create([
            'rba_header_id' => $header->id,
            'unit_id' => $unit->id,
            'status_submission' => 'Pending Supervisor'
        ]);
    }

    public function test_supervisor_can_view_their_unit_submissions()
    {
        $response = $this->actingAs($this->supervisor)->get(route('supervisor.submissions.index'));
        $response->assertStatus(200);
        $response->assertSee('Murni');
    }

    public function test_supervisor_can_validate_submission()
    {
        $response = $this->actingAs($this->supervisor)->post(route('supervisor.submissions.validate', $this->submission));

        $response->assertRedirect(route('supervisor.submissions.index'));
        $this->assertEquals('Validated', $this->submission->fresh()->status_submission);
    }

    public function test_supervisor_can_see_previous_period_pagu_in_awal_column()
    {
        // 1. Create 2025 Perubahan Header with Pagu
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
            'nominal_pagu' => 25000000
        ]);

        // 2. Create detail in current 2026 Murni submission
        RbaDetail::create([
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Gaji 2026',
            'volume' => 12,
            'satuan' => 'Bulan',
            'harga_satuan' => 2000000,
            'nominal_request' => 24000000,
            'created_by' => $this->supervisor->id
        ]);

        // 3. Access supervisor submission show page
        $response = $this->actingAs($this->supervisor)->get(route('supervisor.submissions.show', $this->submission->id));

        $response->assertStatus(200);
        $response->assertSee('AWAL');
        $response->assertSee('Rp 25.000.000');
    }

    public function test_supervisor_can_preview_print_report_with_operator_filters()
    {
        $operator1 = User::factory()->create(['role' => 'Operator', 'unit_id' => $this->supervisor->unit_id, 'name' => 'Operator Satu']);
        $operator2 = User::factory()->create(['role' => 'Operator', 'unit_id' => $this->supervisor->unit_id, 'name' => 'Operator Dua']);

        RbaDetail::create([
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Item Op 1',
            'volume' => 1,
            'satuan' => 'Pkt',
            'harga_satuan' => 1000000,
            'nominal_request' => 1000000,
            'created_by' => $operator1->id
        ]);

        RbaDetail::create([
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Item Op 2',
            'volume' => 1,
            'satuan' => 'Pkt',
            'harga_satuan' => 2000000,
            'nominal_request' => 2000000,
            'created_by' => $operator2->id
        ]);

        $this->submission->update([
            'background' => "Operator Satu: Latar belakang khusus Operator Satu\nOperator Dua: Latar belakang khusus Operator Dua"
        ]);

        // Test Print All Operators (With Background)
        $resAll = $this->actingAs($this->supervisor)->get(route('supervisor.submissions.print-preview', [
            'submission' => $this->submission->id,
            'include_background' => 1
        ]));
        $resAll->assertStatus(200);
        $resAll->assertSee('Item Op 1');
        $resAll->assertSee('Item Op 2');
        $resAll->assertSee('Semua Operator');
        $resAll->assertSee('Latar belakang khusus Operator Satu');
        $resAll->assertSee('Latar belakang khusus Operator Dua');

        // Test Filter Single Operator (Operator 1) - Only Operator 1 background should appear
        $resOp1 = $this->actingAs($this->supervisor)->get(route('supervisor.submissions.print-preview', [
            'submission' => $this->submission->id,
            'include_background' => 1,
            'operator_ids' => [$operator1->id]
        ]));
        $resOp1->assertStatus(200);
        $resOp1->assertSee('Item Op 1');
        $resOp1->assertDontSee('Item Op 2');
        $resOp1->assertSee('Operator Satu');
        $resOp1->assertSee('Latar belakang khusus Operator Satu');
        $resOp1->assertDontSee('Latar belakang khusus Operator Dua');
    }

    public function test_supervisor_can_preview_rba_final_print_report_with_pagu_and_operator_filters()
    {
        $operator1 = User::factory()->create(['role' => 'Operator', 'unit_id' => $this->supervisor->unit_id, 'name' => 'Operator Alpha']);
        $operator2 = User::factory()->create(['role' => 'Operator', 'unit_id' => $this->supervisor->unit_id, 'name' => 'Operator Beta']);

        $this->submission->update([
            'background' => "Operator Alpha: Latar belakang khusus Alpha\nOperator Beta: Latar belakang khusus Beta"
        ]);

        RbaAccountPagu::create([
            'rba_header_id' => $this->submission->rba_header_id,
            'account_code_id' => $this->accountCode->id,
            'nominal_pagu' => 50000000
        ]);

        RbaDetail::create([
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Laptop Op Alpha',
            'volume' => 1,
            'satuan' => 'Unit',
            'harga_satuan' => 15000000,
            'nominal_request' => 15000000,
            'created_by' => $operator1->id
        ]);

        RbaDetail::create([
            'rba_submission_id' => $this->submission->id,
            'account_code_id' => $this->accountCode->id,
            'description' => 'Printer Op Beta',
            'volume' => 1,
            'satuan' => 'Unit',
            'harga_satuan' => 5000000,
            'nominal_request' => 5000000,
            'created_by' => $operator2->id
        ]);

        // 1. Test Print RBA Final All Operators (Both backgrounds should appear)
        $resAll = $this->actingAs($this->supervisor)->get(route('supervisor.submissions.print-preview-final', [
            'submission' => $this->submission->id,
            'include_background' => 1
        ]));
        $resAll->assertStatus(200);
        $resAll->assertSee('USULAN RINCIAN RENCANA BELANJA DAN ANGGARAN DAN PAGU FINAL (RBA FINAL)');
        $resAll->assertSee('PAGU FINAL (Rp)');
        $resAll->assertSee('50.000.000');
        $resAll->assertSee('Laptop Op Alpha');
        $resAll->assertSee('Printer Op Beta');
        $resAll->assertSee('Operator Alpha');
        $resAll->assertSee('Operator Beta');
        $resAll->assertSee('Latar belakang khusus Alpha');
        $resAll->assertSee('Latar belakang khusus Beta');

        // 2. Test Print RBA Final Filter Single Operator (Only Operator Alpha background should appear)
        $resAlpha = $this->actingAs($this->supervisor)->get(route('supervisor.submissions.print-preview-final', [
            'submission' => $this->submission->id,
            'include_background' => 1,
            'operator_ids' => [$operator1->id]
        ]));
        $resAlpha->assertStatus(200);
        $resAlpha->assertSee('Laptop Op Alpha');
        $resAlpha->assertDontSee('Printer Op Beta');
        $resAlpha->assertSee('Operator Alpha');
        $resAlpha->assertSee('Latar belakang khusus Alpha');
        $resAlpha->assertDontSee('Latar belakang khusus Beta');
    }
}
