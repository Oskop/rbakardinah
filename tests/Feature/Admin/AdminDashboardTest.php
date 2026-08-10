<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\RbaHeader;
use App\Models\RbaPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_dashboard_and_see_rba_list()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $period = RbaPeriod::create(['name' => 'Murni']);
        $header = RbaHeader::create([
            'year' => 2026,
            'period_id' => $period->id,
            'admin_id' => $admin->id,
            'status_global' => 'Draft'
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Administrator Dashboard - SIPAKAR RBA');
        $response->assertSee('Daftar RBA Historis');
        $response->assertSee('Users');
        $response->assertSee('Units');
    }

    public function test_admin_can_preview_print_report_with_unit_and_operator_filters()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $unit1 = \App\Models\Unit::create(['code' => 'U01', 'name' => 'Unit Rawat Inap']);
        $unit2 = \App\Models\Unit::create(['code' => 'U02', 'name' => 'Unit Farmasi']);

        $period = RbaPeriod::create(['name' => 'Murni']);
        $header = RbaHeader::create([
            'year' => 2026,
            'period_id' => $period->id,
            'admin_id' => $admin->id,
            'status_global' => 'Draft'
        ]);

        $sub1 = \App\Models\RbaSubmission::create(['rba_header_id' => $header->id, 'unit_id' => $unit1->id]);
        $sub2 = \App\Models\RbaSubmission::create(['rba_header_id' => $header->id, 'unit_id' => $unit2->id]);

        $group = \App\Models\KelompokBelanja::create(['kode' => 'KB01', 'name' => 'Group']);
        $ac = \App\Models\AccountCode::create(['kelompok_belanja_id' => $group->id, 'code' => '5.1.01', 'name' => 'Belanja Gaji']);

        $op1 = User::factory()->create(['role' => 'Operator', 'unit_id' => $unit1->id, 'name' => 'Op Inap']);
        $op2 = User::factory()->create(['role' => 'Operator', 'unit_id' => $unit2->id, 'name' => 'Op Farmasi']);

        \App\Models\RbaDetail::create([
            'rba_submission_id' => $sub1->id,
            'account_code_id' => $ac->id,
            'description' => 'Item Inap',
            'volume' => 1,
            'satuan' => 'Pkt',
            'harga_satuan' => 1000,
            'nominal_request' => 1000,
            'created_by' => $op1->id
        ]);

        \App\Models\RbaDetail::create([
            'rba_submission_id' => $sub2->id,
            'account_code_id' => $ac->id,
            'description' => 'Item Farmasi',
            'volume' => 1,
            'satuan' => 'Pkt',
            'harga_satuan' => 2000,
            'nominal_request' => 2000,
            'created_by' => $op2->id
        ]);

        // 1. Test Admin Print All
        $resAll = $this->actingAs($admin)->get(route('admin.headers.print-preview', ['header' => $header->id]));
        $resAll->assertStatus(200);
        $resAll->assertSee('Item Inap');
        $resAll->assertSee('Item Farmasi');
        $resAll->assertSee('Seluruh Unit Kerja');

        // 2. Test Admin Print Filter Unit 1
        $resUnit1 = $this->actingAs($admin)->get(route('admin.headers.print-preview', [
            'header' => $header->id,
            'unit_ids' => [$unit1->id]
        ]));
        $resUnit1->assertStatus(200);
        $resUnit1->assertSee('Item Inap');
        $resUnit1->assertDontSee('Item Farmasi');
        $resUnit1->assertSee('Unit Rawat Inap');

        // 3. Test Admin Print Filter Operator 2
        $resOp2 = $this->actingAs($admin)->get(route('admin.headers.print-preview', [
            'header' => $header->id,
            'operator_ids' => [$op2->id]
        ]));
        $resOp2->assertStatus(200);
        $resOp2->assertDontSee('Item Inap');
        $resOp2->assertSee('Item Farmasi');
        $resOp2->assertSee('Op Farmasi');
    }

    public function test_admin_can_preview_rba_final_print_report_with_pagu_and_unit_operator_filters()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $unit1 = \App\Models\Unit::create(['code' => 'U01', 'name' => 'Unit Rawat Inap']);
        $unit2 = \App\Models\Unit::create(['code' => 'U02', 'name' => 'Unit Farmasi']);

        $period = RbaPeriod::create(['name' => 'Murni']);
        $header = RbaHeader::create([
            'year' => 2026,
            'period_id' => $period->id,
            'admin_id' => $admin->id,
            'status_global' => 'Draft'
        ]);

        $sub1 = \App\Models\RbaSubmission::create(['rba_header_id' => $header->id, 'unit_id' => $unit1->id]);
        $sub2 = \App\Models\RbaSubmission::create(['rba_header_id' => $header->id, 'unit_id' => $unit2->id]);

        $group = \App\Models\KelompokBelanja::create(['kode' => 'KB01', 'name' => 'Group']);
        $ac = \App\Models\AccountCode::create(['kelompok_belanja_id' => $group->id, 'code' => '5.1.01', 'name' => 'Belanja Gaji']);

        \App\Models\RbaAccountPagu::create([
            'rba_header_id' => $header->id,
            'account_code_id' => $ac->id,
            'nominal_pagu' => 100000000
        ]);

        $op1 = User::factory()->create(['role' => 'Operator', 'unit_id' => $unit1->id, 'name' => 'Op Inap']);
        $op2 = User::factory()->create(['role' => 'Operator', 'unit_id' => $unit2->id, 'name' => 'Op Farmasi']);

        \App\Models\RbaDetail::create([
            'rba_submission_id' => $sub1->id,
            'account_code_id' => $ac->id,
            'description' => 'Item Inap',
            'volume' => 1,
            'satuan' => 'Pkt',
            'harga_satuan' => 1000,
            'nominal_request' => 1000,
            'created_by' => $op1->id
        ]);

        \App\Models\RbaDetail::create([
            'rba_submission_id' => $sub2->id,
            'account_code_id' => $ac->id,
            'description' => 'Item Farmasi',
            'volume' => 1,
            'satuan' => 'Pkt',
            'harga_satuan' => 2000,
            'nominal_request' => 2000,
            'created_by' => $op2->id
        ]);

        // 1. Test Admin Print RBA Final All
        $resAll = $this->actingAs($admin)->get(route('admin.headers.print-preview-final', ['header' => $header->id]));
        $resAll->assertStatus(200);
        $resAll->assertSee('USULAN RINCIAN RENCANA BELANJA DAN ANGGARAN DAN PAGU FINAL (RBA FINAL)');
        $resAll->assertSee('PAGU FINAL (Rp)');
        $resAll->assertSee('100.000.000');
        $resAll->assertSee('Item Inap');
        $resAll->assertSee('Item Farmasi');
        $resAll->assertSee('Unit Rawat Inap');
        $resAll->assertSee('Unit Farmasi');

        // 2. Test Admin Print RBA Final Filter Unit 1
        $resUnit1 = $this->actingAs($admin)->get(route('admin.headers.print-preview-final', [
            'header' => $header->id,
            'unit_ids' => [$unit1->id]
        ]));
        $resUnit1->assertStatus(200);
        $resUnit1->assertSee('Item Inap');
        $resUnit1->assertDontSee('Item Farmasi');
        $resUnit1->assertSee('Unit Rawat Inap');
    }
}
