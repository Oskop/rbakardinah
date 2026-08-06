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
use Tests\TestCase;

class OperatorDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected $operator;
    protected $unit;
    protected $header;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unit = Unit::create(['code' => 'U01', 'name' => 'Unit Farmasi']);
        $this->operator = User::create([
            'name' => 'Operator Farmasi',
            'email' => 'operator_farmasi@test.com',
            'password' => bcrypt('password'),
            'role' => 'Operator',
            'unit_id' => $this->unit->id,
        ]);

        $period = RbaPeriod::create(['name' => 'Perencanaan Murni']);
        $this->header = RbaHeader::create([
            'period_id' => $period->id,
            'year' => 2026,
            'admin_id' => 1,
            'status_global' => 'Draft'
        ]);

        $submission = RbaSubmission::create([
            'rba_header_id' => $this->header->id,
            'unit_id' => $this->unit->id,
            'status_submission' => 'Draft',
            'background' => 'Latar Belakang Test'
        ]);

        $group = KelompokBelanja::create(['kode' => 'KB01', 'name' => 'Test Group']);
        $accountCode = AccountCode::create([
            'kelompok_belanja_id' => $group->id,
            'code' => '5.1.01',
            'name' => 'Belanja Obat'
        ]);

        RbaDetail::create([
            'rba_submission_id' => $submission->id,
            'account_code_id' => $accountCode->id,
            'description' => 'Pembelian Obat A',
            'volume' => 10,
            'satuan' => 'Box',
            'harga_satuan' => 50000,
            'nominal_request' => 500000,
            'created_by' => $this->operator->id
        ]);

        RbaAccountPagu::create([
            'rba_header_id' => $this->header->id,
            'account_code_id' => $accountCode->id,
            'nominal_pagu' => 1000000
        ]);
    }

    public function test_operator_can_access_dashboard_and_see_rba_list()
    {
        $response = $this->actingAs($this->operator)->get(route('operator.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('operator.dashboard');
        $response->assertViewHas('rbaData');
        $response->assertSee('Operator Dashboard - SIPAKAR RBA');
        $response->assertSee('2026');
        $response->assertSee('Perencanaan Murni');
    }

    public function test_generic_dashboard_route_redirects_operator_to_operator_dashboard()
    {
        $response = $this->actingAs($this->operator)->get('/dashboard');

        $response->assertRedirect(route('operator.dashboard'));
    }

    public function test_dashboard_displays_operator_breakdown()
    {
        $response = $this->actingAs($this->operator)->get(route('operator.dashboard'));

        $rbaData = $response->viewData('rbaData');
        $this->assertNotEmpty($rbaData);
        $this->assertEquals('Operator Farmasi', $rbaData[0]['operators'][0]['operator_name']);
        $this->assertEquals(500000, $rbaData[0]['operators'][0]['total_usulan']);
    }
}
